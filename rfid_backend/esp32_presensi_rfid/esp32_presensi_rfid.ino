/*
 * =====================================================================
 * FIRMWARE ESP32 PRESENSI RFID MULTI-LOKASI & AUTO-SESI LCD 16X2 (v2.0)
 * Yayasan Pondok Pesantren Al Muttaqin Kota Madiun
 * =====================================================================
 * Perbaikan & Peningkatan Stabilitas (v2.0):
 * 1. Anti-Bentrok MQTT Broker (MQTT Client ID Collision Protection):
 *    - Client ID MQTT otomatis diberi suffix unik berbasis Chip MAC ESP32.
 *    - Jika ada 2 atau lebih alat dinyalakan bersamaan dengan DEVICE_ID yang sama,
 *      keduanya TIDAK AKAN PERNAH saling tendang / putus koneksi di broker Mosquitto.
 * 2. Anti-Freeze Pergantian Sesi (I2C Decoupled Processing):
 *    - Callback MQTT tidak lagi memanggil perintah LCD secara sinkron.
 *    - Perubahan sesi diproses di main loop() secara aman, mencegah I2C deadlock/freeze
 *      saat server mem-broadcast status sesi baru bertubi-tubi.
 * 3. Proteksi I2C Timeout & Recovery:
 *    - Wire.setTimeOut(1000) mencegah CPU ESP32 hang permanen jika bus LCD terganggu.
 * 4. Pemulihan Otomatis Modul RFID RC522 (Brownout & SPI Auto-Recovery):
 *    - Secara periodik memeriksa register status RC522. Jika sensor mengalami drop tegangan
 *      dan macet di jalur SPI, firmware otomatis me-reset dan menginisialisasi ulang RC522.
 * 5. Hardware Task Watchdog Timer (WDT 15 detik):
 *    - Jika terjadi hang tak terduga pada WiFi/I2C/SPI, ESP32 otomatis reboot mandiri
 *      tanpa perlu cabut colok adaptor daya.
 * 6. Langganan Topik MQTT Bersih & Hemat Buffer:
 *    - Menghilangkan langganan redundan yang membanjiri buffer RAM ESP32.
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
// Contoh: "tap_masjid_1" untuk alat 1, "tap_masjid_2" untuk alat 2.
// JIKA DIKOSONGKAN (""), otomatis memakai Serial Chip MAC unik ESP32 (misal: "ESP32-B8D2E14A").
const char* DEVICE_ID = "kantor smp";

// ================= KONFIGURASI WIFI & VPS MQTT =================
const char* WIFI_SSID     = "KANTOR SMP L2";
const char* WIFI_PASSWORD = "";

const char* MQTT_SERVER   = "202.155.17.96";
const int   MQTT_PORT     = 1883;

const char* TOPIC_TAP       = "presensi/santri/tap";
const char* TOPIC_HEARTBEAT = "presensi/santri/heartbeat";

// ================= KONFIGURASI PIN HARDWARE ====================
#define SS_PIN      5
#define RST_PIN     4
#define BUZZER_PIN  25

#define WDT_TIMEOUT_SECONDS 15

MFRC522 rfid(SS_PIN, RST_PIN);
LiquidCrystal_I2C lcd(0x27, 16, 2);
WiFiClient espClient;
PubSubClient mqttClient(espClient);

// Variabel Identitas Perangkat
String activeDeviceId  = "";
String mqttBrokerClientId = "";

// Status Sesi Presensi
bool hasActiveSesi = false;
String currentKategori = "";
String currentSesiName = "";
volatile bool pendingLcdRefresh = false;

// Status Respon Tap Kartu (Decoupled dari MQTT Callback)
bool waitingResponse = false;
unsigned long tapSentAtMs = 0;

bool showingResult = false;
unsigned long lcdResetAtMs = 0;
volatile bool pendingDisplayResult = false;
String respNama = "";
String respStatus = "";

// Timer periodik
unsigned long lastHeartbeatMs = 0;
unsigned long lastRfidCheckMs = 0;

// ================= FUNGSI BACA SERIAL HARDWARE ESP32 ===========
String readHardwareSerial() {
    uint64_t chipid = ESP.getEfuseMac(); // 48-bit Factory MAC unik
    char serialBuf[24];
    snprintf(serialBuf, sizeof(serialBuf), "ESP32-%04X%08X",
             (uint16_t)(chipid >> 32), (uint32_t)chipid);
    return String(serialBuf);
}

// ================= POLA BUNYI BUZZER GPIO 25 ===================
void beepTap() {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(70);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepSuccess() {
    for (int i = 0; i < 2; i++) {
        digitalWrite(BUZZER_PIN, HIGH);
        delay(60);
        digitalWrite(BUZZER_PIN, LOW);
        delay(50);
    }
}

void beepDuplicate() {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(100);
    digitalWrite(BUZZER_PIN, LOW);
    delay(70);
    digitalWrite(BUZZER_PIN, HIGH);
    delay(100);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepWarning() {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(300);
    digitalWrite(BUZZER_PIN, LOW);
}

// ================= HELPER RATA TENGAH LCD 16X2 =================
void printCenter(String teks, int baris) {
    teks.trim();
    if (teks.length() > 16) {
        teks = teks.substring(0, 16);
    }
    int offset = (16 - teks.length()) / 2;
    if (offset < 0) offset = 0;
    lcd.setCursor(0, baris);
    lcd.print("                ");
    lcd.setCursor(offset, baris);
    lcd.print(teks);
}

// ================= TAMPILAN STANDBY / SESI AKTIF ===============
void showStandbyScreen() {
    lcd.clear();
    if (!hasActiveSesi) {
        printCenter("TIDAK ADA SESI", 0);
        printCenter("", 1);
    } else {
        String kat = currentKategori;
        String ses = currentSesiName;
        kat.trim();
        ses.trim();

        printCenter(kat, 0);

        if (kat.equalsIgnoreCase(ses) || ses.length() == 0) {
            printCenter("", 1);
        } else {
            printCenter(ses, 1);
        }
    }
    showingResult = false;
    waitingResponse = false;
}

// ================= PEMULIHAN / RE-INIT MODUL RFID RC522 ========
void checkAndRecoverRC522() {
    byte ver = rfid.PCD_ReadRegister(MFRC522::VersionReg);
    // Nilai normal VersionReg adalah 0x91 atau 0x92. Jika 0x00 atau 0xFF berarti modul tidak merespon (freeze / lepas)
    if (ver == 0x00 || ver == 0xFF) {
        Serial.println("[RC522] Terdeteksi tidak merespon (ver=0x" + String(ver, HEX) + "). Memulihkan koneksi SPI...");
        digitalWrite(RST_PIN, LOW);
        delay(20);
        digitalWrite(RST_PIN, HIGH);
        delay(20);
        rfid.PCD_Init();
        Serial.println("[RC522] Inisialisasi ulang selesai.");
    }
}

// ================= KIRIM HEARTBEAT KE SERVER ===================
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
// CATATAN PENTING: Callback ini berjalan cepat tanpa perintah lambat I2C (Wire)
void mqttCallback(char* topic, byte* payload, unsigned int length) {
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
        // Abaikan jika pesan spesifik ditujukan untuk perangkat lain
        if (targetDevice.length() > 0 && !targetDevice.equalsIgnoreCase(activeDeviceId)) {
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

        bool changed = (hasActiveSesi != adaSesi) ||
                       (currentKategori != nextKategori) ||
                       (currentSesiName != nextSesiName);

        if (changed) {
            hasActiveSesi   = adaSesi;
            currentKategori = nextKategori;
            currentSesiName = nextSesiName;
            // Tandai butuh update layar (dieksekusi aman di loop(), bukan di interrupt callback)
            pendingLcdRefresh = true;
        }
        return;
    }

    // 2. RESPON HASIL TAP KARTU
    if (topStr.indexOf("response") >= 0) {
        String targetDevice = doc["device_id"] | "";
        if (targetDevice.length() > 0 && !targetDevice.equalsIgnoreCase(activeDeviceId)) {
            return; // Respon untuk alat lain
        }

        waitingResponse = false;
        respNama = doc["nama"] | "Santri";
        respStatus = doc["status"] | "BERHASIL ABSEN";
        pendingDisplayResult = true; // Tandai agar loop() yang menampilkan ke LCD
        return;
    }
}

// ================= KONEKSI WIFI & MQTT =========================
void connectWiFi() {
    lcd.clear();
    printCenter("MENGHUBUNGKAN", 0);
    printCenter("WIFI...", 1);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int retry = 0;
    while (WiFi.status() != WL_CONNECTED) {
        delay(400);
        Serial.print(".");
        retry++;
        if (retry > 40) {
            Serial.println("\nWiFi gagal terhubung, mencoba ulang...");
            WiFi.disconnect();
            WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
            retry = 0;
        }
    }
    Serial.println("\nWiFi Terhubung! IP: " + WiFi.localIP().toString());
}

void reconnectMQTT() {
    while (!mqttClient.connected()) {
        Serial.print("Menghubungkan MQTT ke " + String(MQTT_SERVER) + " sebagai [" + mqttBrokerClientId + "] (Device: " + activeDeviceId + ")... ");
        
        // Connect ke broker dengan Client ID yang dijamin unik di seluruh dunia
        if (mqttClient.connect(mqttBrokerClientId.c_str())) {
            Serial.println("Terhubung!");

            // 1. Dengarkan respon tap kartu khusus untuk alat ini
            String topicResp1 = "presensi/santri/response/" + activeDeviceId;
            String topicResp2 = "presensi/devices/" + activeDeviceId + "/response";
            mqttClient.subscribe(topicResp1.c_str(), 1);
            mqttClient.subscribe(topicResp2.c_str(), 1);

            // 2. Dengarkan broadcast sesi aktif spesifik untuk alat ini
            String topicSesiDevice = "presensi/devices/" + activeDeviceId + "/sesi";
            mqttClient.subscribe(topicSesiDevice.c_str(), 1);

            // 3. Fallback broadcast sesi global
            mqttClient.subscribe("presensi/sesi_aktif", 1);

            // Kirim sinyal boot ke backend agar status online terdata & sesi langsung dikirim balik
            publishHeartbeat("boot");
            showStandbyScreen();
        } else {
            Serial.print("Gagal, rc=");
            Serial.println(mqttClient.state());
            delay(3000);
        }
    }
}

// ================= SETUP UTAMA =================================
void setup() {
    Serial.begin(115200);
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);

    // Ambil Serial Unik Chip MAC ESP32
    String hwSerial = readHardwareSerial();

    // Inisialisasi ID perangkat untuk display & registrasi ke sistem web
    if (String(DEVICE_ID).length() > 0) {
        activeDeviceId = String(DEVICE_ID);
    } else {
        activeDeviceId = hwSerial;
    }

    // Client ID MQTT ke Broker WAJIB UNIK di broker Mosquitto:
    // Menggabungkan activeDeviceId dengan 6 digit unik MAC chip agar tidak ada bentrok
    mqttBrokerClientId = activeDeviceId + "-" + hwSerial.substring(hwSerial.length() - 6);

    Serial.println("=========================================");
    Serial.println("DEVICE ID SISTEM : " + activeDeviceId);
    Serial.println("MQTT CLIENT ID   : " + mqttBrokerClientId);
    Serial.println("=========================================");

    // Inisialisasi I2C dengan proteksi timeout (1000ms) agar tidak bisa hang permanen
    Wire.begin(21, 22);
    Wire.setTimeOut(1000);

    lcd.init();
    lcd.backlight();

    // Tampilkan Device ID saat pertama kali dinyalakan
    lcd.clear();
    printCenter("ALAT AKTIF:", 0);
    printCenter(activeDeviceId, 1);
    beepTap();
    delay(1500);

    // Inisialisasi SPI dan Modul RFID RC522
    SPI.begin();
    rfid.PCD_Init();
    delay(50);
    checkAndRecoverRC522();

    // Inisialisasi WiFi & MQTT
    connectWiFi();
    mqttClient.setBufferSize(512);
    mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
    mqttClient.setCallback(mqttCallback);
    reconnectMQTT();

    // Inisialisasi Hardware Task Watchdog Timer (Anti-Hang Auto Reboot)
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

    Serial.println("Sistem Siap! Watchdog Aktif (15 detik).");
}

// ================= LOOP UTAMA ==================================
void loop() {
    // Reset Hardware Watchdog Timer setiap iterasi loop normal
    esp_task_wdt_reset();

    // Pastikan koneksi WiFi & MQTT tetap terjaga
    if (WiFi.status() != WL_CONNECTED) {
        connectWiFi();
    }
    if (!mqttClient.connected()) {
        reconnectMQTT();
    }
    mqttClient.loop();

    // 1. Eksekusi Tampilan Hasil Tap Kartu (Aman dari Interrupt)
    if (pendingDisplayResult) {
        pendingDisplayResult = false;
        lcd.clear();
        printCenter(respNama, 0);
        printCenter(respStatus, 1);

        if (respStatus.equalsIgnoreCase("BERHASIL ABSEN") || 
            respStatus.equalsIgnoreCase("HADIR MASUK") || 
            respStatus.equalsIgnoreCase("HADIR PULANG")) {
            beepSuccess();
        } else if (respStatus.equalsIgnoreCase("SUDAH ABSEN")) {
            beepDuplicate();
        } else {
            beepWarning();
        }

        showingResult = true;
        lcdResetAtMs = millis() + 3000;
    }

    // 2. Eksekusi Refresh Layar Standby jika Sesi Berubah
    if (pendingLcdRefresh && !showingResult && !waitingResponse) {
        pendingLcdRefresh = false;
        showStandbyScreen();
    }

    // 3. Timeout Proteksi Tap: Jika 3 detik tidak ada respon dari server, tampilkan timeout
    if (waitingResponse && (millis() - tapSentAtMs >= 3000)) {
        waitingResponse = false;
        beepWarning();
        lcd.clear();
        printCenter("SERVER TIMEOUT", 0);
        printCenter("COBA LAGI", 1);
        showingResult = true;
        lcdResetAtMs = millis() + 2000;
    }

    // 4. Kembalikan layar LCD ke standby setelah durasi tampilan hasil tap selesai
    if (showingResult && millis() >= lcdResetAtMs) {
        showStandbyScreen();
    }

    // 5. Kirim heartbeat tiap 60 detik agar status Online di Web selalu terpantau
    if (millis() - lastHeartbeatMs > 60000) {
        lastHeartbeatMs = millis();
        publishHeartbeat("ping");
    }

    // 6. Pemeriksaan Kesehatan Modul RFID RC522 setiap 15 detik (Auto-Recovery jika drop tegangan)
    if (!waitingResponse && (millis() - lastRfidCheckMs > 15000)) {
        lastRfidCheckMs = millis();
        checkAndRecoverRC522();
    }

    // 7. Jangan baca kartu baru jika sedang menunggu balasan server atau sedang menampilkan hasil
    if (waitingResponse || showingResult) {
        return;
    }

    // 8. Deteksi Tap Kartu RFID Baru
    if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
        return;
    }

    // Baca UID format HEX 8-digit
    String uidHex = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
        if (rfid.uid.uidByte[i] < 0x10) uidHex += "0";
        uidHex += String(rfid.uid.uidByte[i], HEX);
    }
    uidHex.toUpperCase();

    // Hitung Format Desimal 32-bit dari 4-byte kartu Mifare (normal & byte-reversed)
    uint32_t uidDec32 = 0;
    uint32_t uidDec32Rev = 0;
    String uidHexRev = "";
    if (rfid.uid.size >= 4) {
        // Format Normal RC522
        uidDec32 = ((uint32_t)rfid.uid.uidByte[0] << 24) |
                   ((uint32_t)rfid.uid.uidByte[1] << 16) |
                   ((uint32_t)rfid.uid.uidByte[2] << 8)  |
                   ((uint32_t)rfid.uid.uidByte[3]);

        // Format Byte-Reversed (Big-Endian sesuai USB Desktop Reader)
        uidDec32Rev = ((uint32_t)rfid.uid.uidByte[3] << 24) |
                      ((uint32_t)rfid.uid.uidByte[2] << 16) |
                      ((uint32_t)rfid.uid.uidByte[1] << 8)  |
                      ((uint32_t)rfid.uid.uidByte[0]);

        for (int i = (int)rfid.uid.size - 1; i >= 0; i--) {
            if (rfid.uid.uidByte[i] < 0x10) uidHexRev += "0";
            uidHexRev += String(rfid.uid.uidByte[i], HEX);
        }
        uidHexRev.toUpperCase();
    }

    beepTap();
    Serial.println("Tap HEX: " + uidHex + " (Rev: " + uidHexRev + ") | DEC: " + String(uidDec32) + " (Rev: " + String(uidDec32Rev) + ") | Device: " + activeDeviceId);

    // Tampilkan pesan proses di LCD
    lcd.clear();
    printCenter("MEMPROSES KARTU", 0);
    printCenter("MOHON TUNGGU...", 1);

    waitingResponse = true;
    tapSentAtMs = millis();

    // Format 10 digit zero-padded sesuai format USB Desktop Reader (misal 0086028247)
    char dec32Padded[16];
    snprintf(dec32Padded, sizeof(dec32Padded), "%010lu", (unsigned long)uidDec32);
    char dec32RevPadded[16];
    snprintf(dec32RevPadded, sizeof(dec32RevPadded), "%010lu", (unsigned long)uidDec32Rev);

    // Kirim payload ke MQTT
    StaticJsonDocument<384> doc;
    doc["device_id"]        = activeDeviceId;
    doc["serial"]           = activeDeviceId;
    doc["uid"]              = uidHex;
    doc["rfid_uid"]         = String(uidDec32);
    doc["rfid_uid_pad"]     = String(dec32Padded);
    doc["uid_rev"]          = uidHexRev;
    doc["rfid_uid_rev"]     = String(uidDec32Rev);
    doc["rfid_uid_rev_pad"] = String(dec32RevPadded);
    doc["ip"]               = WiFi.localIP().toString();

    char payload[256];
    serializeJson(doc, payload);
    mqttClient.publish(TOPIC_TAP, payload);

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
}
