/*
 * =====================================================================
 * FIRMWARE ESP32 PRESENSI RFID MULTI-LOKASI & FAST CONTINUOUS TAP (v3.0)
 * Yayasan Pondok Pesantren Al Muttaqin Kota Madiun
 * =====================================================================
 * Peningkatan Utama (v3.0):
 * 1. TAP CEPAT BERUNTUN TANPA TUNGGU MODE STANDBY (Zero-Wait Continuous Tap):
 *    - Saat nama siswa sebelumnya masih tampil di LCD, siswa berikutnya BISA
 *      LANGSUNG MEN-TAP KARTU tanpa menunggu layar kembali ke mode standby!
 *    - Proteksi anti-double-tap hanya menahan kartu dengan UID YANG SAMA selama
 *      2,5 detik; kartu siswa lain langsung diproses seketika.
 * 2. LOADING SUPER CEPAT & TANPA KEDIP (Ultra-Low Latency):
 *    - Mengaktifkan TCP_NODELAY (espClient.setNoDelay(true)) agar paket MQTT
 *      terkirim seketika tanpa jeda Nagle 40-200ms.
 *    - Paket tap dipublikasikan ke MQTT TERLEBIH DAHULU sebelum bunyi buzzer
 *      atau pembaruan LCD.
 *    - Buzzer non-blocking berbasis millis() (NOL delay() di dalam loop).
 *    - Menunda tampilan "MEMPROSES..." 450ms sehingga respon cepat (<450ms)
 *      langsung menampilkan nama siswa tanpa jeda/kedip layar loading.
 *    - Deduplikasi respon via tap_seq dan 1 topik respon bersih (mencegah
 *      buzzer bunyi 2x dan LCD redraw 2x).
 * 3. SINKRONISASI SESI ANTI "TIDAK ADA SESI":
 *    - Pencocokan Device ID toleran spasi/underscore/huruf besar-kecil
 *      ("kantor smp" otomatis cocok dengan "kantor_smp").
 *    - Berlangganan topik sesi raw & normalized sekaligus + sinkronisasi ulang
 *      tiap 15 detik bila belum menerima sesi aktif.
 * 4. Stabilitas Hardware Penuh:
 *    - Client ID MQTT unik berbasis Chip MAC, I2C timeout 1000ms, auto-recovery
 *      RC522, dan Hardware Watchdog Timer (15 detik) aman saat reconnect.
 *
 * Pin Hardware:
 * - RC522 SDA(SS): GPIO 5  | SCK: GPIO 18 | MOSI: GPIO 23 | MISO: GPIO 19 | RST: GPIO 4
 * - LCD 16x2 I2C : SDA: GPIO 21 | SCL: GPIO 22 (Alamat I2C: 0x27)
 * - Buzzer Aktif : GPIO 25
 */

#include <WiFi.h>
#include <PubSubClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <ArduinoJson.h>
#include <esp_task_wdt.h>

// ================= KONFIGURASI PERANGKAT =======================
// Masukkan Device ID yang didaftarkan di Menu Pengaturan Sesi Web.
// JIKA DIKOSONGKAN (""), otomatis memakai Serial Chip MAC unik ESP32.
const char* DEVICE_ID = "kantor smp";

// ================= KONFIGURASI WIFI & VPS MQTT =================
const char* WIFI_SSID     = "KANTOR SMP L2";
const char* WIFI_PASSWORD = "";

const char* MQTT_SERVER   = "202.155.17.96";
const int   MQTT_PORT     = 1883;

const char* TOPIC_TAP       = "presensi/santri/tap";
const char* TOPIC_HEARTBEAT = "presensi/santri/heartbeat";

// ================= KONFIGURASI PIN & TIMING ====================
#define SS_PIN      5
#define RST_PIN     4
#define BUZZER_PIN  25

#define WDT_TIMEOUT_SECONDS       15
#define RESULT_DISPLAY_MS         2500   // Durasi nama tampil di LCD bila tidak ada siswa berikutnya
#define SAME_CARD_COOLDOWN_MS     2500   // Cooldown hanya untuk KARTU YANG SAMA (kartu lain langsung bisa tap!)
#define PREEMPT_INFLIGHT_MS       200    // Kartu berbeda boleh mendahului tap in-flight setelah 200ms
#define PROCESSING_HINT_DELAY_MS  450    // Tampilkan "MEMPROSES..." hanya jika server > 450ms
#define RESPONSE_TIMEOUT_MS       2500   // Batas tunggu respon server
#define SESSION_SYNC_GRACE_MS     2000   // Masa tunggu sinkron sesi awal saat baru konek MQTT

MFRC522 rfid(SS_PIN, RST_PIN);
LiquidCrystal_I2C lcd(0x27, 16, 2);
WiFiClient espClient;
PubSubClient mqttClient(espClient);

// Identitas Perangkat
String activeDeviceId     = "";
String normalizedDeviceId = "";
String mqttBrokerClientId = "";

// Status Sesi Presensi
bool hasActiveSesi        = false;
bool receivedSessionMsg   = false;
unsigned long sessionSyncDeadlineMs = 0;
String currentKategori    = "";
String currentSesiName    = "";
volatile bool pendingLcdRefresh = false;

// Cache LCD untuk update bebas flicker & cepat
String lastRenderedLine0 = "";
String lastRenderedLine1 = "";

// Status Tap Kartu & Continuous Tap
bool waitingResponse       = false;
bool processingHintShown   = false;
unsigned long tapSentAtMs  = 0;
uint32_t currentTapSeq     = 0;
uint32_t lastDisplayedSeq  = 0;

String lastTappedUid       = "";
unsigned long lastTapAtMs  = 0;

bool showingResult         = false;
unsigned long lcdResetAtMs = 0;
volatile bool pendingDisplayResult = false;
String respNama   = "";
String respStatus = "";
uint32_t pendingRespSeq = 0;

// Timer periodik
unsigned long lastHeartbeatMs = 0;
unsigned long lastRfidCheckMs = 0;
bool wdtInitialized = false;

// ================= NON-BLOCKING BUZZER STATE MACHINE ===========
struct BeepStep {
    bool high;
    uint16_t durationMs;
};

BeepStep buzzerQueue[6];
uint8_t buzzerCount = 0;
uint8_t buzzerIdx = 0;
unsigned long buzzerStepEndMs = 0;
bool buzzerActive = false;

void startBuzzerPattern(const BeepStep* steps, uint8_t count) {
    if (count == 0 || count > 6) return;
    for (uint8_t i = 0; i < count; i++) {
        buzzerQueue[i] = steps[i];
    }
    buzzerCount = count;
    buzzerIdx = 0;
    buzzerActive = true;
    digitalWrite(BUZZER_PIN, buzzerQueue[0].high ? HIGH : LOW);
    buzzerStepEndMs = millis() + buzzerQueue[0].durationMs;
}

void updateBuzzer() {
    if (!buzzerActive) return;
    if ((long)(millis() - buzzerStepEndMs) < 0) return;

    buzzerIdx++;
    if (buzzerIdx >= buzzerCount) {
        digitalWrite(BUZZER_PIN, LOW);
        buzzerActive = false;
        return;
    }
    digitalWrite(BUZZER_PIN, buzzerQueue[buzzerIdx].high ? HIGH : LOW);
    buzzerStepEndMs = millis() + buzzerQueue[buzzerIdx].durationMs;
}

void beepTapAsync() {
    static const BeepStep seq[] = { {true, 45}, {false, 10} };
    startBuzzerPattern(seq, 2);
}

void beepSuccessAsync() {
    static const BeepStep seq[] = { {true, 55}, {false, 45}, {true, 55}, {false, 10} };
    startBuzzerPattern(seq, 4);
}

void beepDuplicateAsync() {
    static const BeepStep seq[] = { {true, 90}, {false, 60}, {true, 90}, {false, 10} };
    startBuzzerPattern(seq, 4);
}

void beepWarningAsync() {
    static const BeepStep seq[] = { {true, 220}, {false, 10} };
    startBuzzerPattern(seq, 2);
}

void safeWdtReset() {
    if (wdtInitialized) {
        esp_task_wdt_reset();
    }
}

// ================= HELPER NORMALISASI DEVICE ID ================
String normalizeDeviceId(String s) {
    s.trim();
    s.toLowerCase();
    String out = "";
    bool prevUnderscore = false;
    for (unsigned int i = 0; i < s.length(); i++) {
        char c = s.charAt(i);
        if (c == ' ' || c == '-' || c == '_') {
            if (!prevUnderscore) {
                out += '_';
                prevUnderscore = true;
            }
        } else {
            out += c;
            prevUnderscore = false;
        }
    }
    return out;
}

bool isForThisDevice(const String& targetDevice) {
    if (targetDevice.length() == 0) return true;
    return normalizeDeviceId(targetDevice) == normalizedDeviceId;
}

// ================= FUNGSI BACA SERIAL HARDWARE ESP32 ===========
String readHardwareSerial() {
    uint64_t chipid = ESP.getEfuseMac();
    char serialBuf[24];
    snprintf(serialBuf, sizeof(serialBuf), "ESP32-%04X%08X",
             (uint16_t)(chipid >> 32), (uint32_t)chipid);
    return String(serialBuf);
}

// ================= HELPER LCD 16X2 CEPAT & BEBAS FLICKER =======
void printCenter(String teks, int baris, bool forceWrite = false) {
    teks.trim();
    if (teks.length() > 16) {
        teks = teks.substring(0, 16);
    }

    char lineBuf[17];
    memset(lineBuf, ' ', 16);
    lineBuf[16] = '\0';

    int len = teks.length();
    int offset = (16 - len) / 2;
    if (offset < 0) offset = 0;
    for (int i = 0; i < len && (offset + i) < 16; i++) {
        lineBuf[offset + i] = teks.charAt(i);
    }

    String formatted = String(lineBuf);
    if (!forceWrite) {
        if (baris == 0 && formatted == lastRenderedLine0) return;
        if (baris == 1 && formatted == lastRenderedLine1) return;
    }

    lcd.setCursor(0, baris);
    lcd.print(lineBuf);

    if (baris == 0) {
        lastRenderedLine0 = formatted;
    } else {
        lastRenderedLine1 = formatted;
    }
}

// ================= TAMPILAN STANDBY / SESI AKTIF ===============
void showStandbyScreen(bool forceWrite = false) {
    if (!hasActiveSesi) {
        if (!receivedSessionMsg && (long)(millis() - sessionSyncDeadlineMs) < 0) {
            printCenter("SINKRON SESI...", 0, forceWrite);
            printCenter(activeDeviceId, 1, forceWrite);
        } else {
            printCenter("TIDAK ADA SESI", 0, forceWrite);
            printCenter("", 1, forceWrite);
        }
    } else {
        String kat = currentKategori;
        String ses = currentSesiName;
        kat.trim();
        ses.trim();

        printCenter(kat, 0, forceWrite);
        if (kat.equalsIgnoreCase(ses) || ses.length() == 0) {
            printCenter("", 1, forceWrite);
        } else {
            printCenter(ses, 1, forceWrite);
        }
    }
    showingResult = false;
    waitingResponse = false;
    processingHintShown = false;
}

// ================= PEMULIHAN / RE-INIT MODUL RFID RC522 ========
void checkAndRecoverRC522() {
    byte ver = rfid.PCD_ReadRegister(MFRC522::VersionReg);
    if (ver == 0x00 || ver == 0xFF) {
        Serial.println("[RC522] Tidak merespon (ver=0x" + String(ver, HEX) + "). Memulihkan SPI...");
        digitalWrite(RST_PIN, LOW);
        delay(10);
        digitalWrite(RST_PIN, HIGH);
        delay(10);
        rfid.PCD_Init();
        Serial.println("[RC522] Pemulihan selesai.");
    }
}

// ================= KIRIM HEARTBEAT / REQUEST SESI ==============
void publishHeartbeat(const char* eventName) {
    if (!mqttClient.connected()) return;

    StaticJsonDocument<256> doc;
    doc["serial"]    = activeDeviceId;
    doc["device_id"] = activeDeviceId;
    doc["ip"]        = WiFi.localIP().toString();
    doc["event"]     = eventName;

    char buffer[256];
    serializeJson(doc, buffer);
    mqttClient.publish(TOPIC_HEARTBEAT, buffer);
}

// ================= CALLBACK MQTT DARI DAEMON VPS ================
// Ringan & cepat: TIDAK ada perintah I2C LCD di sini
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    if (length == 0) return;

    StaticJsonDocument<512> doc;
    DeserializationError err = deserializeJson(doc, payload, length);
    if (err) return;

    String topStr = String(topic);

    // 1. UPDATE SESI AKTIF
    if (topStr.indexOf("sesi") >= 0 || topStr.indexOf("display") >= 0) {
        String targetDevice = doc["device_id"] | "";
        if (targetDevice.length() == 0) {
            targetDevice = doc["serial"] | "";
        }
        // Cocokkan secara toleran (spasi/underscore/huruf besar-kecil)
        if (targetDevice.length() > 0 && !isForThisDevice(targetDevice)) {
            return;
        }

        bool adaSesi = doc["ada_sesi"] | false;
        String statusStr = doc["status"] | "";
        if (statusStr.equalsIgnoreCase("aktif")) {
            adaSesi = true;
        }

        String nextKategori = "";
        String nextSesiName = "";
        if (adaSesi) {
            nextKategori = doc["kategori"] | "Presensi";
            const char* nSesi = doc["nama_sesi"] | doc["sesi"] | "";
            if (strlen(nSesi) > 0) {
                nextSesiName = String(nSesi);
            } else {
                nextSesiName = nextKategori;
            }
        }

        bool firstSync = !receivedSessionMsg;
        receivedSessionMsg = true;

        bool changed = firstSync ||
                       (hasActiveSesi != adaSesi) ||
                       (currentKategori != nextKategori) ||
                       (currentSesiName != nextSesiName);

        if (changed) {
            hasActiveSesi   = adaSesi;
            currentKategori = nextKategori;
            currentSesiName = nextSesiName;
            pendingLcdRefresh = true;
        }
        return;
    }

    // 2. RESPON HASIL TAP KARTU
    if (topStr.indexOf("response") >= 0) {
        String targetDevice = doc["device_id"] | "";
        if (targetDevice.length() > 0 && !isForThisDevice(targetDevice)) {
            return;
        }

        uint32_t respSeq = doc["tap_seq"] | 0;
        // Abaikan respon lama jika siswa berikutnya sudah men-tap kartu baru
        if (respSeq > 0 && respSeq != currentTapSeq) {
            return;
        }
        // Abaikan duplikat respon untuk tap yang sama (langsung di callback)
        if (respSeq > 0 && (respSeq == lastDisplayedSeq || respSeq == pendingRespSeq)) {
            return;
        }

        waitingResponse = false;
        processingHintShown = false;
        pendingRespSeq = respSeq;
        lastDisplayedSeq = respSeq;
        respNama = doc["nama"] | "Santri";
        respStatus = doc["status"] | "BERHASIL ABSEN";
        pendingDisplayResult = true;
        return;
    }
}

// ================= KONEKSI WIFI & MQTT =========================
void connectWiFi() {
    printCenter("MENGHUBUNGKAN", 0);
    printCenter("WIFI...", 1);

    WiFi.mode(WIFI_STA);
    WiFi.setSleep(false); // Matikan power-saving WiFi agar latensi MQTT minimal
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int retry = 0;
    while (WiFi.status() != WL_CONNECTED) {
        safeWdtReset();
        delay(250);
        Serial.print(".");
        retry++;
        if (retry > 40) {
            Serial.println("\nWiFi gagal terhubung, mencoba ulang...");
            WiFi.disconnect();
            WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
            retry = 0;
        }
    }
    espClient.setNoDelay(true);
    Serial.println("\nWiFi Terhubung! IP: " + WiFi.localIP().toString());
}

void reconnectMQTT() {
    while (!mqttClient.connected()) {
        safeWdtReset();
        if (WiFi.status() != WL_CONNECTED) {
            connectWiFi();
        }
        espClient.setNoDelay(true);

        Serial.print("Menghubungkan MQTT ke " + String(MQTT_SERVER) + " [" + mqttBrokerClientId + "] (Device: " + activeDeviceId + ")... ");
        if (mqttClient.connect(mqttBrokerClientId.c_str())) {
            Serial.println("Terhubung!");
            espClient.setNoDelay(true);

            // 1. Topik respon tap kartu (1 topik bersih sesuai activeDeviceId agar tidak duplikat)
            String topicResp = "presensi/devices/" + activeDeviceId + "/response";
            mqttClient.subscribe(topicResp.c_str(), 0);

            // 2. Topik sesi aktif perangkat (raw & normalized agar kebal beda spasi/underscore)
            String topicSesi = "presensi/devices/" + activeDeviceId + "/sesi";
            mqttClient.subscribe(topicSesi.c_str(), 0);
            if (!activeDeviceId.equalsIgnoreCase(normalizedDeviceId)) {
                String topicSesiNorm = "presensi/devices/" + normalizedDeviceId + "/sesi";
                mqttClient.subscribe(topicSesiNorm.c_str(), 0);
            }

            // 3. Fallback topik status sesi bersama
            mqttClient.subscribe("presensi/santri/status_sesi", 0);
            mqttClient.subscribe("presensi/sesi_aktif", 0);

            sessionSyncDeadlineMs = millis() + SESSION_SYNC_GRACE_MS;
            lastHeartbeatMs = millis();

            publishHeartbeat("boot");
            showStandbyScreen();
        } else {
            Serial.print("Gagal, rc=");
            Serial.println(mqttClient.state());
            for (int i = 0; i < 10; i++) {
                safeWdtReset();
                delay(200);
            }
        }
    }
}

// ================= SETUP UTAMA =================================
void setup() {
    Serial.begin(115200);
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);

    String hwSerial = readHardwareSerial();
    if (String(DEVICE_ID).length() > 0) {
        activeDeviceId = String(DEVICE_ID);
    } else {
        activeDeviceId = hwSerial;
    }
    activeDeviceId.trim();
    normalizedDeviceId = normalizeDeviceId(activeDeviceId);

    mqttBrokerClientId = normalizedDeviceId + "-" + hwSerial.substring(hwSerial.length() - 6);

    Serial.println("=========================================");
    Serial.println("FIRMWARE         : ESP32 PRESENSI v3.0");
    Serial.println("DEVICE ID SISTEM : " + activeDeviceId);
    Serial.println("DEVICE ID NORM   : " + normalizedDeviceId);
    Serial.println("MQTT CLIENT ID   : " + mqttBrokerClientId);
    Serial.println("=========================================");

    // Inisialisasi I2C standar 100kHz (stabil di semua modul LCD PCF8574) dengan proteksi timeout
    Wire.begin(21, 22);
    Wire.setTimeOut(1000);

    lcd.init();
    lcd.backlight();
    lcd.clear();

    printCenter("ALAT AKTIF:", 0, true);
    printCenter(activeDeviceId, 1, true);
    digitalWrite(BUZZER_PIN, HIGH);
    delay(55);
    digitalWrite(BUZZER_PIN, LOW);
    delay(800);

    // Inisialisasi SPI & Modul RFID RC522
    SPI.begin();
    rfid.PCD_Init();
    delay(30);
    checkAndRecoverRC522();

    // Inisialisasi Watchdog Timer sebelum koneksi jaringan
    #if ESP_ARDUINO_VERSION >= ESP_ARDUINO_VERSION_VAL(3, 0, 0)
        esp_task_wdt_config_t wdt_config = {
            .timeout_ms = WDT_TIMEOUT_SECONDS * 1000,
            .idle_core_mask = 0,
            .trigger_panic = true
        };
        esp_task_wdt_init(&wdt_config);
        esp_task_wdt_add(NULL);
    #else
        esp_task_wdt_init(WDT_TIMEOUT_SECONDS, true);
        esp_task_wdt_add(NULL);
    #endif
    wdtInitialized = true;

    // Inisialisasi WiFi & MQTT
    connectWiFi();
    mqttClient.setBufferSize(512);
    mqttClient.setSocketTimeout(2);
    mqttClient.setKeepAlive(30);
    mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
    mqttClient.setCallback(mqttCallback);
    reconnectMQTT();

    Serial.println("Sistem Siap! Mode Fast Continuous Tap & Watchdog Aktif.");
}

// ================= LOOP UTAMA ==================================
void loop() {
    safeWdtReset();
    updateBuzzer();

    // Jaga koneksi WiFi & MQTT tetap prima
    if (WiFi.status() != WL_CONNECTED) {
        connectWiFi();
    }
    if (!mqttClient.connected()) {
        reconnectMQTT();
    }
    mqttClient.loop();

    unsigned long nowMs = millis();

    // 1. Tampilkan Hasil Tap Kartu (segera tanpa memblokir siswa berikutnya!)
    if (pendingDisplayResult) {
        pendingDisplayResult = false;
        printCenter(respNama, 0);
        printCenter(respStatus, 1);

        if (respStatus.equalsIgnoreCase("BERHASIL ABSEN") ||
            respStatus.equalsIgnoreCase("HADIR MASUK") ||
            respStatus.equalsIgnoreCase("HADIR PULANG")) {
            beepSuccessAsync();
        } else if (respStatus.equalsIgnoreCase("SUDAH ABSEN")) {
            beepDuplicateAsync();
        } else {
            beepWarningAsync();
        }

        showingResult = true;
        lcdResetAtMs = nowMs + RESULT_DISPLAY_MS;
    }

    // 2. Tampilkan layar "MEMPROSES..." HANYA jika server belum membalas > 450ms
    if (waitingResponse && !processingHintShown && (nowMs - tapSentAtMs >= PROCESSING_HINT_DELAY_MS)) {
        processingHintShown = true;
        printCenter("MEMPROSES...", 0);
        printCenter("MOHON TUNGGU", 1);
    }

    // 3. Refresh Layar Standby jika Sesi Berubah atau masa grace sinkron awal selesai
    if (!showingResult && !waitingResponse) {
        if (pendingLcdRefresh) {
            pendingLcdRefresh = false;
            showStandbyScreen();
        } else if (!receivedSessionMsg && !hasActiveSesi && (long)(nowMs - sessionSyncDeadlineMs) >= 0) {
            receivedSessionMsg = true; // Tampilkan status akhir jika belum ada balasan
            showStandbyScreen();
        }
    }

    // 4. Timeout Proteksi Tap (2,5 detik)
    if (waitingResponse && (nowMs - tapSentAtMs >= RESPONSE_TIMEOUT_MS)) {
        waitingResponse = false;
        processingHintShown = false;
        beepWarningAsync();
        printCenter("SERVER TIMEOUT", 0);
        printCenter("COBA LAGI", 1);
        showingResult = true;
        lcdResetAtMs = nowMs + 1500;
    }

    // 5. Kembalikan LCD ke standby bila durasi tampilan hasil sudah habis & tidak ada tap baru
    if (showingResult && !waitingResponse && (long)(nowMs - lcdResetAtMs) >= 0) {
        showStandbyScreen();
    }

    // 6. Heartbeat & Auto-Sync Sesi:
    //    - Setiap 15 detik bila belum ada sesi aktif (agar cepat pulih otomatis)
    //    - Setiap 60 detik bila sesi sudah aktif
    unsigned long hbInterval = hasActiveSesi ? 60000UL : 15000UL;
    if (nowMs - lastHeartbeatMs > hbInterval) {
        lastHeartbeatMs = nowMs;
        publishHeartbeat(hasActiveSesi ? "ping" : "sync_sesi");
    }

    // 7. Pemeriksaan Kesehatan Modul RFID RC522 setiap 15 detik (saat idle)
    if (!waitingResponse && !showingResult && (nowMs - lastRfidCheckMs > 15000UL)) {
        lastRfidCheckMs = nowMs;
        checkAndRecoverRC522();
    }

    // 8. DETEKSI TAP KARTU RFID SECARA KONTINU (TIDAK PERLU TUNGGU STANDBY!)
    //    Catatan: `showingResult` sengaja TIDAK memblokir pembacaan kartu!
    if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
        return;
    }

    // Baca UID format HEX 8-digit
    String uidHex = "";
    uidHex.reserve(16);
    for (byte i = 0; i < rfid.uid.size; i++) {
        if (rfid.uid.uidByte[i] < 0x10) uidHex += "0";
        uidHex += String(rfid.uid.uidByte[i], HEX);
    }
    uidHex.toUpperCase();

    // Hentikan komunikasi kartu segera agar pembaca siap untuk kartu berikutnya
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();

    // Proteksi Anti-Double-Tap KHUSUS UNTUK KARTU YANG SAMA (kartu siswa lain tetap langsung masuk!)
    if (uidHex == lastTappedUid && (nowMs - lastTapAtMs) < SAME_CARD_COOLDOWN_MS) {
        return;
    }

    // Jika tap kartu lain masih in-flight < 200ms, tunggu sekejap agar tidak tabrakan
    if (waitingResponse && (nowMs - tapSentAtMs) < PREEMPT_INFLIGHT_MS) {
        return;
    }

    // Hitung Format Desimal 32-bit (normal & byte-reversed)
    uint32_t uidDec32 = 0;
    uint32_t uidDec32Rev = 0;
    String uidHexRev = "";
    if (rfid.uid.size >= 4) {
        uidDec32 = ((uint32_t)rfid.uid.uidByte[0] << 24) |
                   ((uint32_t)rfid.uid.uidByte[1] << 16) |
                   ((uint32_t)rfid.uid.uidByte[2] << 8)  |
                   ((uint32_t)rfid.uid.uidByte[3]);

        uidDec32Rev = ((uint32_t)rfid.uid.uidByte[3] << 24) |
                      ((uint32_t)rfid.uid.uidByte[2] << 16) |
                      ((uint32_t)rfid.uid.uidByte[1] << 8)  |
                      ((uint32_t)rfid.uid.uidByte[0]);

        uidHexRev.reserve(16);
        for (int i = (int)rfid.uid.size - 1; i >= 0; i--) {
            if (rfid.uid.uidByte[i] < 0x10) uidHexRev += "0";
            uidHexRev += String(rfid.uid.uidByte[i], HEX);
        }
        uidHexRev.toUpperCase();
    }

    char dec32Padded[16];
    snprintf(dec32Padded, sizeof(dec32Padded), "%010lu", (unsigned long)uidDec32);
    char dec32RevPadded[16];
    snprintf(dec32RevPadded, sizeof(dec32RevPadded), "%010lu", (unsigned long)uidDec32Rev);

    currentTapSeq++;
    lastTappedUid       = uidHex;
    lastTapAtMs         = nowMs;
    waitingResponse     = true;
    processingHintShown = false;
    showingResult       = false;
    tapSentAtMs         = nowMs;

    // KIRIM PAYLOAD MQTT TERLEBIH DAHULU (latensi minimum!)
    StaticJsonDocument<384> doc;
    doc["device_id"]        = activeDeviceId;
    doc["serial"]           = activeDeviceId;
    doc["tap_seq"]          = currentTapSeq;
    doc["uid"]              = uidHex;
    doc["rfid_uid"]         = String(uidDec32);
    doc["rfid_uid_pad"]     = String(dec32Padded);
    doc["uid_rev"]          = uidHexRev;
    doc["rfid_uid_rev"]     = String(uidDec32Rev);
    doc["rfid_uid_rev_pad"] = String(dec32RevPadded);
    doc["ip"]               = WiFi.localIP().toString();

    char payload[320];
    serializeJson(doc, payload);
    mqttClient.publish(TOPIC_TAP, payload);

    // Buzzer tap non-blocking (0ms delay!)
    beepTapAsync();

    Serial.println("[TAP #" + String(currentTapSeq) + "] HEX:" + uidHex + " | DEC:" + String(uidDec32) + " | Dev:" + activeDeviceId);
}
