/*
 * =====================================================================
 * FIRMWARE ESP32 PRESENSI RFID MULTI-LOKASI (AUTO HARDWARE SERIAL ID)
 * Yayasan Pondok Pesantren Al Muttaqin Kota Madiun
 * =====================================================================
 * Keunggulan:
 * 1. Membaca Nomor Seri Unik Pabrik ESP32 (ESP.getEfuseMac()) secara otomatis.
 *    1 file kodingan yang sama bisa di-upload ke banyak alat ESP32 tanpa takut
 *    tertukar! Contoh ID otomatis: ESP32-A4CF1298B3E0
 * 2. Saat dicolok listrik & konek WiFi/MQTT, alat otomatis mengirim sinyal
 *    "boot/heartbeat" sehingga langsung muncul di web /pengaturan-sesi!
 * 3. Mendengarkan topik balasan khusus serial alat sendiri:
 *    presensi/santri/response/<SERIAL_ESP32> (LCD tidak akan tertukar antar alat).
 *
 * Pin Hardware:
 * - RC522 SDA(SS): GPIO 5  | SCK: GPIO 18 | MOSI: GPIO 23 | MISO: GPIO 19 | RST: GPIO 4
 * - LCD 16x2 I2C : SDA: GPIO 21 | SCL: GPIO 22 (Alamat 0x27)
 * - Buzzer Aktif : GPIO 25
 */

#include <WiFi.h>
#include <PubSubClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <ArduinoJson.h>

// ================= KONFIGURASI WIFI & VPS MQTT =================
const char* WIFI_SSID     = "NAMA_WIFI_PONDOK";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_PONDOK";

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

// Variabel dinamis berbasis Serial Pabrik ESP32
String deviceSerial = "";
String topicResponseSpecific = "";
unsigned long lastHeartbeatMs = 0;
unsigned long lcdResetAtMs = 0;
bool showingResult = false;

// ================= FUNGSI BACA SERIAL UNIK ESP32 ===============
String readHardwareSerial() {
    uint64_t chipid = ESP.getEfuseMac(); // 48-bit Factory MAC / Chip ID unik permanen
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

void beepWarning() {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(400);
    digitalWrite(BUZZER_PIN, LOW);
}

void showStandbyScreen() {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("TEMPELKAN KARTU ");
    lcd.setCursor(0, 1);
    // Tampilkan 10 digit akhir serial di pojok bawah agar teknisi mudah mengenali fisik alat
    String shortCode = deviceSerial.length() > 10 ? deviceSerial.substring(deviceSerial.length() - 10) : deviceSerial;
    lcd.print("ID:" + shortCode);
    showingResult = false;
}

// ================= KIRIM HEARTBEAT SERIAL KE SERVER ============
void publishHeartbeat(const char* eventName) {
    if (!mqttClient.connected()) return;

    StaticJsonDocument<200> doc;
    doc["serial"]    = deviceSerial;
    doc["device_id"] = deviceSerial;
    doc["ip"]        = WiFi.localIP().toString();
    doc["event"]     = eventName;

    char buffer[200];
    serializeJson(doc, buffer);
    mqttClient.publish(TOPIC_HEARTBEAT, buffer);
}

// ================= CALLBACK BALASAN DARI SERVER ================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    StaticJsonDocument<256> doc;
    DeserializationError err = deserializeJson(doc, payload, length);
    if (err) return;

    String nama   = doc["nama"]   | "Santri";
    String status = doc["status"] | "Terproses";

    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print(nama.substring(0, 16));
    lcd.setCursor(0, 1);
    lcd.print(status.substring(0, 16));

    if (nama == "Tak Terdaftar" || nama == "Alat Nonaktif" || nama == "Server Offline") {
        beepWarning();
    } else {
        beepSuccess();
    }

    showingResult = true;
    lcdResetAtMs = millis() + 3000;
}

// ================= KONEKSI WIFI & MQTT =========================
void connectWiFi() {
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Menghubungkan");
    lcd.setCursor(0, 1);
    lcd.print("WiFi...");

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
        Serial.print("Menghubungkan MQTT sebagai " + deviceSerial + "...");
        if (mqttClient.connect(deviceSerial.c_str())) {
            Serial.println("Terhubung!");
            // Hanya dengarkan topik milik Serial ESP32 ini sendiri agar tidak tertukar
            mqttClient.subscribe(topicResponseSpecific.c_str());
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

    deviceSerial = readHardwareSerial();
    topicResponseSpecific = "presensi/santri/response/" + deviceSerial;

    Serial.println("=========================================");
    Serial.println("SERIAL HARDWARE ESP32: " + deviceSerial);
    Serial.println("=========================================");

    Wire.begin(21, 22);
    lcd.init();
    lcd.backlight();

    // Tampilkan Serial Unik saat alat baru menyala
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("SERIAL ALAT INI:");
    lcd.setCursor(0, 1);
    lcd.print(deviceSerial.substring(0, 16));
    beepTap();
    delay(2500);

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

    // Kembalikan layar LCD ke standby setelah 3 detik menampilkan hasil tap
    if (showingResult && millis() >= lcdResetAtMs) {
        showStandbyScreen();
    }

    // Kirim heartbeat tiap 60 detik agar status Online di Web selalu terpantau
    if (millis() - lastHeartbeatMs > 60000) {
        lastHeartbeatMs = millis();
        publishHeartbeat("ping");
    }

    // Cek kartu RFID baru
    if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
        return;
    }

    String uidStr = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
        if (rfid.uid.uidByte[i] < 0x10) uidStr += "0";
        uidStr += String(rfid.uid.uidByte[i], HEX);
    }
    uidStr.toUpperCase();

    beepTap();
    Serial.println("Tap UID: " + uidStr + " | Serial: " + deviceSerial);

    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("Memproses Kartu");
    lcd.setCursor(0, 1);
    lcd.print("UID: " + uidStr.substring(0, 11));

    StaticJsonDocument<200> doc;
    doc["serial"]    = deviceSerial;
    doc["device_id"] = deviceSerial;
    doc["uid"]       = uidStr;
    doc["ip"]        = WiFi.localIP().toString();

    char payload[200];
    serializeJson(doc, payload);
    mqttClient.publish(TOPIC_TAP, payload);

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
}
