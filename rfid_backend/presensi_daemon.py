#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Presensi RFID Daemon - Yayasan Pondok Pesantren Al Muttaqin Madiun
Mendengarkan tap kartu RFID dari ESP32 via MQTT, memvalidasi data santri di MySQL,
menyimpan log riwayat presensi harian, dan mengirim balasan status ke LCD alat.
"""

import os
import sys
import json
import time
import logging
from datetime import datetime
from pathlib import Path

# Load environment variables jika file .env tersedia
try:
    from dotenv import load_dotenv
    env_path = Path(__file__).resolve().parent / ".env"
    if env_path.exists():
        load_dotenv(dotenv_path=env_path)
except ImportError:
    pass

import mysql.connector
from mysql.connector import Error as MySQLError
import paho.mqtt.client as mqtt

# ================= KONFIGURASI =================
MQTT_BROKER = os.getenv("MQTT_BROKER", "127.0.0.1")
MQTT_PORT = int(os.getenv("MQTT_PORT", "1883"))
MQTT_TOPIC_TAP = os.getenv("MQTT_TOPIC_TAP", "presensi/santri/tap")
MQTT_TOPIC_RESPONSE = os.getenv("MQTT_TOPIC_RESPONSE", "presensi/santri/response")
MQTT_CLIENT_ID = os.getenv("MQTT_CLIENT_ID", f"Muttaqin_RFID_Backend_{int(time.time())}")

DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", "3306")),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "db_muttaqin"),
    "autocommit": True,
    "charset": "utf8mb4"
}

DEBOUNCE_SECONDS = int(os.getenv("DEBOUNCE_SECONDS", "4"))
ALLOW_DUPLICATE_TAP_PER_DAY = os.getenv("ALLOW_DUPLICATE_TAP_PER_DAY", "false").lower() == "true"

# File cache untuk deteksi kartu terakhir (membantu fitur live pairing di dashboard)
LAST_TAP_LOG_FILE = Path("/tmp/rfid_last_tap.json")

# In-memory cache debounce {uid: timestamp}
tap_history = {}

# ================= LOGGING SETUP =================
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)

# ================= DATABASE CONNECTION =================
def create_db_connection():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        logging.info(f"Berhasil terhubung ke database '{DB_CONFIG['database']}' di {DB_CONFIG['host']}:{DB_CONFIG['port']}.")
        return conn
    except MySQLError as e:
        logging.error(f"Gagal koneksi ke database MySQL: {e}")
        return None

def format_lcd_name(full_name: str) -> str:
    """
    Format nama agar pas dengan batas 16 karakter LCD 16x2.
    Jika nama panjang, perpendek nama tengah/belakang atau potong rapi di 16 karakter.
    """
    if not full_name:
        return "Santri"
    
    clean_name = full_name.strip()
    if len(clean_name) <= 16:
        return clean_name
    
    parts = clean_name.split()
    if len(parts) >= 2:
        # Contoh: Moch Rokib Fastabiqul -> Moch Rokib
        short_two = f"{parts[0]} {parts[1]}"
        if len(short_two) <= 16:
            return short_two
        # Jika bagian pertama masih panjang
        return parts[0][:16]
        
    return clean_name[:16]

def save_last_tap_info(uid: str, device_id: str, is_registered: bool, name: str = ""):
    """Menyimpan kartu terakhir yang di-tap untuk membantu admin di dashboard saat pairing."""
    try:
        data = {
            "uid": uid,
            "device_id": device_id,
            "is_registered": is_registered,
            "name": name,
            "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }
        LAST_TAP_LOG_FILE.write_text(json.dumps(data), encoding="utf-8")
    except Exception as e:
        logging.debug(f"Gagal menulis cache last tap: {e}")

def process_tap(db, uid: str, device_id: str) -> dict:
    """
    Logika utama: mencari santri di tabel siswas / santri,
    mencatat kehadiran harian, dan menghasilkan respon status.
    """
    clean_uid = uid.strip().upper()
    now = datetime.now()
    today_date = now.strftime("%Y-%m-%d")
    current_time = now.strftime("%H:%M:%S")

    cursor = db.cursor(dictionary=True)

    # 1. Cari santri di tabel siswas (atau view santri)
    query_find = """
        SELECT id, nama_lengkap, kamar, status 
        FROM siswas 
        WHERE UPPER(TRIM(rfid_uid)) = %s
        LIMIT 1
    """
    cursor.execute(query_find, (clean_uid,))
    santri = cursor.fetchone()

    # KASUS A: KARTU TIDAK TERDAFTAR
    if not santri:
        cursor.close()
        logging.warning(f"[UNREGISTERED] UID '{clean_uid}' dari device '{device_id}' belum dipetakan ke santri.")
        save_last_tap_info(clean_uid, device_id, is_registered=False)
        return {
            "nama": "Tak Terdaftar",
            "status": "Hubungi Pamong"
        }

    # KASUS B: KARTU TERDAFTAR
    siswa_id = santri["id"]
    nama_lengkap = santri.get("nama_lengkap", "")
    lcd_name = format_lcd_name(nama_lengkap)

    save_last_tap_info(clean_uid, device_id, is_registered=True, name=nama_lengkap)

    # 2. Cek apakah sudah pernah presensi hari ini
    if not ALLOW_DUPLICATE_TAP_PER_DAY:
        query_check_today = """
            SELECT id FROM presensi_santris 
            WHERE siswa_id = %s AND tanggal = %s 
            LIMIT 1
        """
        cursor.execute(query_check_today, (siswa_id, today_date))
        already_present = cursor.fetchone()

        if already_present:
            cursor.close()
            logging.info(f"[DUPLICATE] Santri '{nama_lengkap}' (UID: {clean_uid}) sudah tercatat hadir hari ini.")
            return {
                "nama": lcd_name,
                "status": "Sudah Hadir"
            }

    # 3. Simpan riwayat kehadiran ke presensi_santris
    query_insert = """
        INSERT INTO presensi_santris (siswa_id, rfid_uid, device_id, tanggal, waktu, status, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, 'Hadir', NOW(), NOW())
    """
    try:
        cursor.execute(query_insert, (siswa_id, clean_uid, device_id, today_date, current_time))
        logging.info(f"[SUCCESS] Presensi Santri Sukses: '{nama_lengkap}' [{clean_uid}] pada {today_date} {current_time}")
    except MySQLError as err:
        logging.error(f"Gagal menyimpan ke presensi_santris: {err}")
    finally:
        cursor.close()

    return {
        "nama": lcd_name,
        "status": "Hadir / Terdaftar"
    }

# ================= MQTT HANDLERS =================
def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        logging.info(f"Terhubung ke Broker MQTT ({MQTT_BROKER}:{MQTT_PORT})!")
        logging.info(f"Mulai berlangganan topik: '{MQTT_TOPIC_TAP}'...")
        client.subscribe(MQTT_TOPIC_TAP, qos=1)
    else:
        logging.error(f"Koneksi MQTT Gagal dengan kode: {rc}")

def on_message(client, userdata, msg):
    try:
        payload_raw = msg.payload.decode("utf-8").strip()
        logging.info(f"Menerima tap dari [{msg.topic}]: {payload_raw}")

        try:
            data = json.loads(payload_raw)
        except json.JSONDecodeError:
            logging.warning(f"Payload bukan JSON valid: {payload_raw}")
            return

        uid = data.get("uid") or data.get("rfid_uid")
        device_id = data.get("device_id", "ESP32_Presensi_Santri")

        if not uid:
            logging.warning("Pesan diterima tanpa parameter 'uid'. Diabaikan.")
            return

        clean_uid = str(uid).strip().upper()
        now_ts = time.time()

        # Proteksi Anti-Spam (Debounce)
        if clean_uid in tap_history:
            elapsed = now_ts - tap_history[clean_uid]
            if elapsed < DEBOUNCE_SECONDS:
                logging.info(f"Debounce diaktifkan: UID '{clean_uid}' baru di-tap {elapsed:.1f} detik lalu. Diabaikan.")
                return

        tap_history[clean_uid] = now_ts

        # Pastikan koneksi DB aktif
        db = userdata.get("db")
        if not db or not db.is_connected():
            logging.warning("Koneksi DB terputus, melakukan reconnect...")
            db = create_db_connection()
            userdata["db"] = db

        if not db:
            logging.error("Tidak dapat memproses tap karena DB offline.")
            response_payload = {
                "nama": "Server Offline",
                "status": "Coba Sesaat Lagi"
            }
        else:
            response_payload = process_tap(db, clean_uid, device_id)

        # Kirim balasan ke topik respons ESP32
        response_json = json.dumps(response_payload)
        client.publish(MQTT_TOPIC_RESPONSE, response_json, qos=1)
        logging.info(f"Respons dikirim ke [{MQTT_TOPIC_RESPONSE}]: {response_json}")

    except Exception as ex:
        logging.error(f"Terjadi kesalahan saat memproses pesan MQTT: {ex}", exc_info=True)

# ================= RUNNER =================
def main():
    logging.info("=" * 60)
    logging.info("Memulai Daemon Presensi RFID Yayasan Muttaqin Kota Madiun")
    logging.info(f"Broker: {MQTT_BROKER}:{MQTT_PORT}")
    logging.info(f"Sub: {MQTT_TOPIC_TAP} | Pub: {MQTT_TOPIC_RESPONSE}")
    logging.info("=" * 60)

    db = None
    retry_count = 0
    while not db:
        db = create_db_connection()
        if not db:
            retry_count += 1
            logging.warning(f"Menunggu koneksi DB... percobaan #{retry_count} dalam 5 detik.")
            time.sleep(5)

    # Inisialisasi Paho MQTT (Mendukung versi 1.x dan 2.x)
    try:
        from paho.mqtt.enums import CallbackAPIVersion
        client = mqtt.Client(
            CallbackAPIVersion.VERSION2,
            client_id=MQTT_CLIENT_ID,
            userdata={"db": db}
        )
    except (ImportError, AttributeError):
        client = mqtt.Client(
            client_id=MQTT_CLIENT_ID,
            userdata={"db": db}
        )

    client.on_connect = on_connect
    client.on_message = on_message

    try:
        client.connect(MQTT_BROKER, MQTT_PORT, keepalive=60)
        client.loop_forever()
    except KeyboardInterrupt:
        logging.info("Daemon dihentikan oleh pengguna.")
    finally:
        if db and db.is_connected():
            db.close()
        client.disconnect()
        logging.info("Daemon presensi selesai.")

if __name__ == "__main__":
    main()
