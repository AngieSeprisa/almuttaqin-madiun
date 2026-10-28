#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Presensi RFID Daemon - Multi-Lokasi & Sesi Otomatis
Yayasan Pondok Pesantren Al Muttaqin Madiun

Mendengarkan tap kartu RFID dari berbagai perangkat ESP32 via MQTT,
menentukan sesi aktif berbasis lokasi & waktu lokal, memvalidasi santri / pegawai di MySQL,
mencegah duplikasi per sesi, dan mengirim balasan status ke LCD 16x2.
"""

import os
import sys
import json
import time
import logging
from datetime import datetime, timedelta
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
MQTT_CLIENT_ID = os.getenv("MQTT_CLIENT_ID", f"Muttaqin_MultiRFID_Backend_{int(time.time())}")

DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", "3306")),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_NAME", "db_muttaqin"),
    "autocommit": True,
    "charset": "utf8mb4"
}

DEBOUNCE_SECONDS = int(os.getenv("DEBOUNCE_SECONDS", "3"))

# File cache untuk live auto-detect pairing kartu di dashboard
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

# ================= DATABASE HELPERS =================
def create_db_connection():
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        logging.info(f"Berhasil terhubung ke database '{DB_CONFIG['database']}' di {DB_CONFIG['host']}:{DB_CONFIG['port']}.")
        return conn
    except MySQLError as e:
        logging.error(f"Gagal koneksi ke database MySQL: {e}")
        return None

def format_lcd_text(text: str, max_len: int = 16) -> str:
    """Format teks agar tidak melebihi batas kolom LCD 16x2."""
    if not text:
        return ""
    clean = text.strip()
    return clean[:max_len]

def format_lcd_name(full_name: str) -> str:
    """Format nama santri/pegawai maksimal 16 karakter agar pas di baris LCD."""
    if not full_name:
        return "Santri"
    clean_name = full_name.strip()
    if len(clean_name) <= 16:
        return clean_name
    parts = clean_name.split()
    if len(parts) >= 2:
        short_two = f"{parts[0]} {parts[1]}"
        if len(short_two) <= 16:
            return short_two
        return parts[0][:16]
    return clean_name[:16]

def save_last_tap_info(uid: str, device_id: str, is_registered: bool, name: str = ""):
    """Menyimpan kartu terakhir untuk fitur live pairing kartu di dashboard."""
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

def get_active_sesi(cursor, device_id: str, current_time: str):
    """
    Mencari sesi aktif di master_sesis yang cocok dengan device_id dan jam saat ini.
    """
    # 1. Cek sesi spesifik untuk device_id atau global
    query = """
        SELECT id, kode_sesi, nama_sesi, jam_mulai, jam_selesai, toleransi_menit, kategori
        FROM master_sesis
        WHERE (device_id_target = %s OR device_id_target IS NULL)
          AND (
            (jam_mulai <= jam_selesai AND %s BETWEEN jam_mulai AND jam_selesai)
            OR
            (jam_mulai > jam_selesai AND (%s >= jam_mulai OR %s <= jam_selesai))
          )
        ORDER BY (device_id_target = %s) DESC, jam_mulai ASC
        LIMIT 1
    """
    cursor.execute(query, (device_id, current_time, current_time, current_time, device_id))
    sesi = cursor.fetchone()

    # 2. Jika tidak ada sesi terjadwal pada jam ini, gunakan sesi fallback default
    if not sesi:
        fallback_query = "SELECT id, kode_sesi, nama_sesi, jam_mulai, jam_selesai, toleransi_menit, kategori FROM master_sesis ORDER BY id ASC LIMIT 1"
        cursor.execute(fallback_query)
        sesi = cursor.fetchone()

    return sesi

def find_entity_by_uid(cursor, clean_uid: str):
    """
    Mencari entitas pemilik kartu:
    1. Cek tabel siswas (santri/siswa)
    2. Cek tabel users (guru/pegawai)
    """
    # Cek Santri / Siswa
    cursor.execute("SELECT id, nama_lengkap, kamar, status FROM siswas WHERE UPPER(TRIM(rfid_uid)) = %s LIMIT 1", (clean_uid,))
    santri = cursor.fetchone()
    if santri:
        return {
            "type": "santri",
            "id": santri["id"],
            "nama": santri["nama_lengkap"],
            "sub": santri.get("kamar", "Santri")
        }

    # Cek Guru / Pegawai
    cursor.execute("SELECT id, name, email FROM users WHERE UPPER(TRIM(rfid_uid)) = %s LIMIT 1", (clean_uid,))
    user = cursor.fetchone()
    if user:
        return {
            "type": "guru_pegawai",
            "id": user["id"],
            "nama": user["name"],
            "sub": "Guru/Pegawai"
        }

    return None

def process_tap(db, uid: str, device_id: str) -> dict:
    """
    Logika Pemrosesan Multi-Lokasi & Sesi Otomatis:
    1. Validasi Device
    2. Tentukan Sesi Berdasarkan Jam & Device
    3. Cari Pemilik Kartu (Santri / Pegawai)
    4. Anti-Duplikasi Per Sesi
    5. Simpan Log Presensi Terpadu
    """
    clean_uid = uid.strip().upper()
    now = datetime.now()
    today_date = now.strftime("%Y-%m-%d")
    current_time = now.strftime("%H:%M:%S")
    now_full = now.strftime("%Y-%m-%d %H:%M:%S")

    cursor = db.cursor(dictionary=True)

    # 1. Validasi Device ID (Pastikan terdaftar di master_devices)
    cursor.execute("SELECT id, device_id, nama_lokasi, status_aktif FROM master_devices WHERE device_id = %s LIMIT 1", (device_id,))
    device = cursor.fetchone()
    if not device:
        # Auto-register device jika belum terdaftar agar alat baru langsung bisa bekerja
        cursor.execute(
            "INSERT INTO master_devices (device_id, nama_lokasi, kategori_area, status_aktif, created_at, updated_at) VALUES (%s, %s, 'pondok', 1, NOW(), NOW())",
            (device_id, f"Perangkat {device_id}")
        )
        logging.info(f"[DEVICE AUTO-REGISTER] Device '{device_id}' otomatis ditambahkan ke master_devices.")

    # 2. Penentuan Sesi Dinamis
    sesi = get_active_sesi(cursor, device_id, current_time)
    sesi_id = sesi["id"] if sesi else 1
    sesi_name = sesi["nama_sesi"] if sesi else "Presensi"

    # 3. Pengecekan Entitas Pemilik Kartu (Santri atau Guru/Pegawai)
    entity = find_entity_by_uid(cursor, clean_uid)

    if not entity:
        cursor.close()
        logging.warning(f"[UNREGISTERED] UID '{clean_uid}' dari device '{device_id}' tidak dikenal.")
        save_last_tap_info(clean_uid, device_id, is_registered=False)
        return {
            "nama": "Tak Terdaftar",
            "status": "Hubungi Pamong"
        }

    entity_name = entity["nama"]
    entity_type = entity["type"]
    entity_id = entity["id"]
    lcd_name = format_lcd_name(entity_name)

    save_last_tap_info(clean_uid, device_id, is_registered=True, name=entity_name)

    # 4. Anti-Duplikasi Per Sesi
    query_check = """
        SELECT id FROM presensis
        WHERE sesi_id = %s AND tipe_entitas = %s AND entitas_id = %s AND DATE(waktu_tap) = %s
        LIMIT 1
    """
    cursor.execute(query_check, (sesi_id, entity_type, entity_id, today_date))
    already_tapped = cursor.fetchone()

    if already_tapped:
        cursor.close()
        logging.info(f"[DUPLICATE] '{entity_name}' sudah absen pada sesi '{sesi_name}' hari ini.")
        return {
            "nama": lcd_name,
            "status": "Sudah Hadir"
        }

    # 5. Tentukan Status Kehadiran (Tepat Waktu vs Terlambat)
    status_kehadiran = "tepat_waktu"
    if sesi and sesi.get("toleransi_menit"):
        try:
            jam_mulai_dt = datetime.strptime(f"{today_date} {sesi['jam_mulai']}", "%Y-%m-%d %H:%M:%S")
            toleransi_dt = jam_mulai_dt + timedelta(minutes=int(sesi["toleransi_menit"]))
            if now > toleransi_dt:
                status_kehadiran = "terlambat"
        except Exception:
            pass

    if sesi and sesi.get("kategori") == "makan":
        status_kehadiran = "sudah_makan"

    # 6. Simpan ke Tabel Konsolidasi Terpadu `presensis`
    query_insert = """
        INSERT INTO presensis (device_id, sesi_id, tipe_entitas, entitas_id, rfid_uid, waktu_tap, status_kehadiran, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
    """
    cursor.execute(query_insert, (device_id, sesi_id, entity_type, entity_id, clean_uid, now_full, status_kehadiran))

    # Dukungan kompatibilitas tabel lama presensi_santris
    if entity_type == "santri":
        try:
            cursor.execute("""
                INSERT INTO presensi_santris (siswa_id, rfid_uid, device_id, tanggal, waktu, status, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, 'Hadir', NOW(), NOW())
            """, (entity_id, clean_uid, device_id, today_date, current_time))
        except Exception:
            pass

    cursor.close()

    logging.info(f"[SUCCESS] Presensi '{entity_name}' tercatat pada sesi '{sesi_name}' ({status_kehadiran}).")

    # Format status untuk baris kedua LCD 16x2
    # Contoh: "SUBUH: Hadir" atau "Hadir: Tepat Wkt"
    short_sesi = sesi.get("kode_sesi", "HADIR") if sesi else "HADIR"
    lcd_status = format_lcd_text(f"{short_sesi}: Hadir", 16)

    return {
        "nama": lcd_name,
        "status": lcd_status
    }

# ================= MQTT CALLBACKS =================
def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        logging.info(f"Terhubung ke Broker MQTT ({MQTT_BROKER}:{MQTT_PORT})!")
        logging.info(f"Berlangganan topik tap: '{MQTT_TOPIC_TAP}'")
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

        # Proteksi Debounce (Anti-spam tap)
        if clean_uid in tap_history:
            elapsed = now_ts - tap_history[clean_uid]
            if elapsed < DEBOUNCE_SECONDS:
                logging.info(f"Debounce: UID '{clean_uid}' baru di-tap {elapsed:.1f} detik lalu. Diabaikan.")
                return

        tap_history[clean_uid] = now_ts

        # Pastikan koneksi DB aktif
        db = userdata.get("db")
        if not db or not db.is_connected():
            logging.warning("Koneksi DB terputus, mencoba reconnect...")
            db = create_db_connection()
            userdata["db"] = db

        if not db:
            logging.error("Database offline! Tidak dapat memproses tap.")
            response_payload = {
                "nama": "Server Offline",
                "status": "Coba Sesaat Lagi"
            }
        else:
            response_payload = process_tap(db, clean_uid, device_id)

        # Kirim balasan status ke topik respons ESP32
        response_json = json.dumps(response_payload)
        client.publish(MQTT_TOPIC_RESPONSE, response_json, qos=1)
        logging.info(f"Respons dikirim ke [{MQTT_TOPIC_RESPONSE}]: {response_json}")

    except Exception as ex:
        logging.error(f"Error memproses pesan MQTT: {ex}", exc_info=True)

# ================= RUNNER =================
def main():
    logging.info("=" * 65)
    logging.info("Memulai Multi-Location Presensi Daemon Yayasan Al Muttaqin")
    logging.info(f"Broker: {MQTT_BROKER}:{MQTT_PORT}")
    logging.info(f"Topik Sub: {MQTT_TOPIC_TAP} | Topik Pub: {MQTT_TOPIC_RESPONSE}")
    logging.info("=" * 65)

    db = None
    retry_count = 0
    while not db:
        db = create_db_connection()
        if not db:
            retry_count += 1
            logging.warning(f"Menunggu koneksi DB... percobaan #{retry_count} dalam 5 detik.")
            time.sleep(5)

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

if __name__ == "__main__":
    main()
