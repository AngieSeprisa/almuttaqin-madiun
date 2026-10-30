/*
 * =====================================================================
 * FIRMWARE ESP32 PRESENSI RFID MULTI-LOKASI & AUTO-SESI LCD 16X2
 * Yayasan Pondok Pesantren Al Muttaqin Kota Madiun
 * =====================================================================
 * Spesifikasi & Fitur:
 * 1. Konfigurasi DEVICE_ID (misal: "tap_masjid") yang sinkron dengan Web Panel.
 *    Jika dikosongkan (""), otomatis membaca Chip MAC ESP32.
 * 2. Tampilan LCD 16x2 Siaga (Standby):
 *    - Jika Tidak Ada Sesi Aktif: Baris 1 "TIDAK ADA SESI", Baris 2 kosong.
 *    - Jika Ada Sesi Aktif:
 *      * Baris 1: Kategori (misal: "Sholat")
 *      * Baris 2: Nama Sesi (misal: "Subuh")
 *      * Jika Kategori == Nama Sesi, Baris 2 dibiarkan kosong.
 * 3. Tampilan Respon Tap Kartu:
 *    - Baris 1: Nama Santri / Pegawai (maks 16 karakter).
 *    - Baris 2: Status ("BERHASIL ABSEN" / "SUDAH ABSEN" / "TIDAK TERDAFTAR" / "AKSES DITOLAK").
 * 4. Proteksi Timeout: Jika 3 detik tidak ada balasan dari broker/daemon VPS,
 *    layar LCD otomatis kembali ke standby tanpa tertahan ("MEMPROSES KARTU...").
 * 5. Format UID dikirim dalam HEX ("uid") dan Format Desimal 32-bit ("rfid_uid").
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

// ================= KONFIGURASI PERANGKAT =======================
// Masukkan Device ID yang didaftarkan di Menu Pengaturan Sesi Web (default: "tap_masjid")
const char* DEVICE_ID = "tap_masjid";

// ================= KONFIGURASI WIFI & VPS MQTT =================
const char* WIFI_SSID     = "Kota_pintar_kls_LT.1";
const char* WIFI_PASSWORD = "12345678";

const char* MQTT_SERVER   = "202.155.17.96";
const int   MQTT_PORT     = 1883;

const char* TOPIC_TAP       = "presensi/santri/tap";
const char* TOPIC_HEARTBEAT = "presensi/santri/heartbeat";

// ================= KONFIGURASI PIN HARDWARE ====================
#define SS_PIN      5
#define RST_PIN     4
#define BUZZER_PIN  25

MFRC522 rfid(SS_PIN, RST_PIN);
LiquidCrystal_I2C lcd(0x27, 16, 2);
WiFiClient espClient;
PubSubClient mqttClient(espClient);

// Variabel status & kontrol layar LCD
String activeDeviceId = "";
bool hasActiveSesi = false;
String currentKategori = "";
String currentSesiName = "";

bool waitingResponse = false;
unsigned long tapSentAtMs = 0;

bool showingResult = false;
unsigned long lcdResetAtMs = 0;

unsigned long lastHeartbeatMs = 0;

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
    delay(80);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepSuccess() {
    for (int i = 0; i < 2; i++) {
        digitalWrite(BUZZER_PIN, HIGH);
        delay(70);
        digitalWrite(BUZZER_PIN, LOW);
        delay(60);
    }
}

void beepDuplicate() {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(120);
    digitalWrite(BUZZER_PIN, LOW);
    delay(80);
    digitalWrite(BUZZER_PIN, HIGH);
    delay(120);
    digitalWrite(BUZZER_PIN, LOW);
}

void beepWarning() {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(400);
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
        // Spesifikasi 1: Tidak ada sesi -> Baris 0 "TIDAK ADA SESI", Baris 1 kosong
        printCenter("TIDAK ADA SESI", 0);
        printCenter("", 1);
    } else {
        // Spesifikasi 2: Ada sesi -> Baris 0 Kategori, Baris 1 Nama Sesi
        String kat = currentKategori;
        String ses = currentSesiName;
        kat.trim();
        ses.trim();

        printCenter(kat, 0);

        // Jika kategori dan nama sesi sama (atau nama kosong), kosongkan baris 1
        if (kat.equalsIgnoreCase(ses) || ses.length() == 0) {
            printCenter("", 1);
        } else {
            printCenter(ses, 1);
        }
    }
    showingResult = false;
    waitingResponse = false;
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
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    StaticJsonDocument<384> doc;
    DeserializationError err = deserializeJson(doc, payload, length);
    if (err) return;

    String topStr = String(topic);

    // 1. UPDATE SESI AKTIF (Topik: presensi/devices/{device_id}/sesi atau presensi/sesi_aktif)
    if (topStr.indexOf("sesi") >= 0) {
        String targetDevice = doc["device_id"] | "";
        if (targetDevice.length() > 0 && !targetDevice.equalsIgnoreCase(activeDeviceId) && topStr.indexOf("sesi_aktif") < 0) {
            return; // Pesan untuk device lain, abaikan
        }

        bool adaSesi = doc["ada_sesi"] | false;
        String statusStr = doc["status"] | "";
        if (statusStr.equalsIgnoreCase("aktif")) {
            adaSesi = true;
        }

        if (adaSesi) {
            hasActiveSesi   = true;
            currentKategori = doc["kategori"]  | "Presensi";
            currentSesiName = doc["nama_sesi"] | currentKategori;
        } else {
            hasActiveSesi   = false;
            currentKategori = "";
            currentSesiName = "";
        }

        // Jika tidak sedang menampilkan hasil tap atau menunggu respon, segera perbarui layar
        if (!showingResult && !waitingResponse) {
            showStandbyScreen();
        }
        return;
    }

    // 2. RESPON HASIL TAP KARTU (Topik: presensi/santri/response/{device_id} dll)
    if (topStr.indexOf("response") >= 0) {
        waitingResponse = false; // Batalkan status tunggu

        String nama   = doc["nama"]   | "Santri";
        String status = doc["status"] | "BERHASIL ABSEN";

        lcd.clear();
        printCenter(nama, 0);
        printCenter(status, 1);

        if (status.equalsIgnoreCase("BERHASIL ABSEN")) {
            beepSuccess();
        } else if (status.equalsIgnoreCase("SUDAH ABSEN")) {
            beepDuplicate();
        } else {
            // "TIDAK TERDAFTAR" atau "AKSES DITOLAK"
            beepWarning();
        }

        showingResult = true;
        lcdResetAtMs = millis() + 3000;
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

    while (WiFi.status() != WL_CONNECTED) {
        delay(400);
        Serial.print(".");
    }
    Serial.println("\nWiFi Terhubung! IP: " + WiFi.localIP().toString());
}

void reconnectMQTT() {
    while (!mqttClient.connected()) {
        Serial.print("Menghubungkan MQTT sebagai " + activeDeviceId + "...");
        if (mqttClient.connect(activeDeviceId.c_str())) {
            Serial.println("Terhubung!");

            // 1. Dengarkan respon tap khusus untuk alat ini
            String topicResp1 = "presensi/santri/response/" + activeDeviceId;
            String topicResp2 = "presensi/devices/" + activeDeviceId + "/response";
            mqttClient.subscribe(topicResp1.c_str());
            mqttClient.subscribe(topicResp2.c_str());

            // 2. Dengarkan broadcast sesi aktif (khusus device ini & global)
            String topicSesiDevice = "presensi/devices/" + activeDeviceId + "/sesi";
            mqttClient.subscribe(topicSesiDevice.c_str());
            mqttClient.subscribe("presensi/sesi_aktif");

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

void setup() {
    Serial.begin(115200);
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(BUZZER_PIN, LOW);

    // Inisialisasi ID perangkat: gunakan DEVICE_ID atau fallback ke Hardware Serial unik
    if (String(DEVICE_ID).length() > 0) {
        activeDeviceId = String(DEVICE_ID);
    } else {
        activeDeviceId = readHardwareSerial();
    }

    Serial.println("=========================================");
    Serial.println("DEVICE ID AKTIF: " + activeDeviceId);
    Serial.println("=========================================");

    Wire.begin(21, 22);
    lcd.init();
    lcd.backlight();

    // Tampilkan Device ID saat pertama kali dinyalakan
    lcd.clear();
    printCenter("ALAT AKTIF:", 0);
    printCenter(activeDeviceId, 1);
    beepTap();
    delay(2000);

    SPI.begin();
    rfid.PCD_Init();

    connectWiFi();

    mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
    mqttClient.setCallback(mqttCallback);
    reconnectMQTT();
}

void loop() {
    if (WiFi.status() != WL_CONNECTED) {
        connectWiFi();
    }
    if (!mqttClient.connected()) {
        reconnectMQTT();
    }
    mqttClient.loop();

    // 1. Timeout Proteksi Tap: Jika 3 detik tidak ada respon dari server, tampilkan timeout
    if (waitingResponse && (millis() - tapSentAtMs >= 3000)) {
        waitingResponse = false;
        beepWarning();
        lcd.clear();
        printCenter("SERVER TIMEOUT", 0);
        printCenter("COBA LAGI", 1);
        showingResult = true;
        lcdResetAtMs = millis() + 2000;
    }

    // 2. Kembalikan layar LCD ke standby setelah 3 detik menampilkan hasil tap
    if (showingResult && millis() >= lcdResetAtMs) {
        showStandbyScreen();
    }

    // 3. Kirim heartbeat tiap 60 detik agar status Online di Web selalu terpantau
    if (millis() - lastHeartbeatMs > 60000) {
        lastHeartbeatMs = millis();
        publishHeartbeat("ping");
    }

    // 4. Deteksi Tap Kartu RFID Baru
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

    // Tampilkan pesan proses
    lcd.clear();
    printCenter("MEMPROSES KARTU", 0);
    printCenter("MOHON TUNGGU...", 1);

    waitingResponse = true;
    tapSentAtMs = millis();

    // Kirim payload ke MQTT
    StaticJsonDocument<384> doc;
    doc["device_id"]    = activeDeviceId;
    doc["serial"]       = activeDeviceId;
    doc["uid"]          = uidHex;
    doc["rfid_uid"]     = String(uidDec32);
    doc["uid_rev"]      = uidHexRev;
    doc["rfid_uid_rev"] = String(uidDec32Rev);
    doc["ip"]           = WiFi.localIP().toString();

    char payload[256];
    serializeJson(doc, payload);
    mqttClient.publish(TOPIC_TAP, payload);

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
}
