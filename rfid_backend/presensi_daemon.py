#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Presensi RFID Daemon - Multi-Lokasi, Auto-Serial ESP32 & Sesi Otomatis
Yayasan Pondok Pesantren Al Muttaqin Madiun

Mendengarkan heartbeat & tap kartu RFID dari perangkat ESP32 via MQTT menggunakan
Serial Unik Hardware (eFuse MAC), mendaftarkan alat otomatis ke master_devices,
menentukan sesi aktif berbasis lokasi & waktu lokal, memvalidasi santri / pegawai,
mencegah duplikasi per sesi, dan mengirim balasan status ke LCD 16x2 spesifik alat.
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
MQTT_TOPIC_HEARTBEAT = os.getenv("MQTT_TOPIC_HEARTBEAT", "presensi/santri/heartbeat")
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

# File cache untuk live auto-detect pairing kartu & serial perangkat di dashboard web
LAST_TAP_LOG_FILE = Path("/tmp/rfid_last_tap.json")
LAST_DEVICE_LOG_FILE = Path("/tmp/rfid_last_device.json")

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

def save_last_tap_info(uid: str, device_id: str, is_registered: bool, name: str = "", ip_addr: str = ""):
    """Menyimpan kartu terakhir untuk fitur live pairing kartu di dashboard."""
    try:
        data = {
            "uid": uid,
            "device_id": device_id,
            "ip": ip_addr,
            "is_registered": is_registered,
            "name": name,
            "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }
        LAST_TAP_LOG_FILE.write_text(json.dumps(data), encoding="utf-8")
    except Exception as e:
        logging.debug(f"Gagal menulis cache last tap: {e}")

def save_last_device_info(device_id: str, ip_addr: str = "", event: str = "online"):
    """Menyimpan perangkat ESP32 terakhir yang menyala/mengirim sinyal."""
    try:
        data = {
            "device_id": device_id,
            "ip": ip_addr,
            "event": event,
            "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }
        LAST_DEVICE_LOG_FILE.write_text(json.dumps(data), encoding="utf-8")
    except Exception as e:
        logging.debug(f"Gagal menulis cache last device: {e}")

def sync_device_presence(cursor, device_id: str, ip_addr: str = "") -> dict:
    """
    Memastikan Serial/ID perangkat terdaftar di master_devices dan memperbarui last_seen_at & ip_address.
    Mengembalikan row perangkat dari database.
    """
    cursor.execute(
        "SELECT id, device_id, nama_lokasi, status_aktif FROM master_devices WHERE device_id = %s LIMIT 1",
        (device_id,)
    )
    device = cursor.fetchone()

    if not device:
        default_label = "Alat Baru (Belum Diberi Lokasi)"
        try:
            cursor.execute(
                """
                INSERT INTO master_devices (device_id, nama_lokasi, kategori_area, ip_address, status_aktif, last_seen_at, created_at, updated_at)
                VALUES (%s, %s, 'pondok', %s, 1, NOW(), NOW(), NOW())
                """,
                (device_id, default_label, ip_addr or None)
            )
        except Exception:
            cursor.execute(
                """
                INSERT INTO master_devices (device_id, nama_lokasi, kategori_area, status_aktif, created_at, updated_at)
                VALUES (%s, %s, 'pondok', 1, NOW(), NOW())
                """,
                (device_id, default_label)
            )
        logging.info(f"[AUTO-DISCOVERY] Serial perangkat baru '{device_id}' ({ip_addr or '-'}) otomatis terdaftar.")
        device = {
            "device_id": device_id,
            "nama_lokasi": default_label,
            "status_aktif": 1
        }
    else:
        try:
            if ip_addr:
                cursor.execute(
                    "UPDATE master_devices SET last_seen_at = NOW(), ip_address = %s, updated_at = NOW() WHERE device_id = %s",
                    (ip_addr, device_id)
                )
            else:
                cursor.execute(
                    "UPDATE master_devices SET last_seen_at = NOW(), updated_at = NOW() WHERE device_id = %s",
                    (device_id,)
                )
        except Exception:
            pass

    return device

def get_active_sesi(cursor, device_id: str, current_time: str):
    """
    Mencari sesi aktif di master_sesis yang cocok dengan device_id dan jam saat ini.
    """
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

    # Jika tidak ada sesi terjadwal pada jam ini, gunakan sesi fallback default
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
    cursor.execute("SELECT id, nama_lengkap, kamar, status FROM siswas WHERE UPPER(TRIM(rfid_uid)) = %s LIMIT 1", (clean_uid,))
    santri = cursor.fetchone()
    if santri:
        return {
            "type": "santri",
            "id": santri["id"],
            "nama": santri["nama_lengkap"],
            "sub": santri.get("kamar", "Santri")
        }

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

def process_tap(db, uid: str, device_id: str, ip_addr: str = "") -> dict:
    """
    Logika Pemrosesan Multi-Lokasi & Sesi Otomatis berbasis Serial Perangkat:
    1. Sinkronisasi Serial Perangkat & Cek Status Aktif
    2. Tentukan Sesi Berdasarkan Jam & Serial Device
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

    # 1. Sinkronisasi Serial Device & Cek apakah alat diaktifkan oleh admin
    device = sync_device_presence(cursor, device_id, ip_addr)
    save_last_device_info(device_id, ip_addr=ip_addr, event="tap")

    if not device.get("status_aktif", 1):
        cursor.close()
        logging.warning(f"[DISABLED DEVICE] Tap dari '{device_id}' ditolak karena status perangkat nonaktif.")
        return {
            "device_id": device_id,
            "nama": "Alat Nonaktif",
            "status": "Hubungi Admin"
        }

    # 2. Penentuan Sesi Dinamis
    sesi = get_active_sesi(cursor, device_id, current_time)
    sesi_id = sesi["id"] if sesi else 1
    sesi_name = sesi["nama_sesi"] if sesi else "Presensi"

    # 3. Pengecekan Entitas Pemilik Kartu (Santri atau Guru/Pegawai)
    entity = find_entity_by_uid(cursor, clean_uid)

    if not entity:
        cursor.close()
        logging.warning(f"[UNREGISTERED] UID '{clean_uid}' dari serial '{device_id}' tidak dikenal.")
        save_last_tap_info(clean_uid, device_id, is_registered=False, ip_addr=ip_addr)
        return {
            "device_id": device_id,
            "nama": "Tak Terdaftar",
            "status": "Hubungi Pamong"
        }

    entity_name = entity["nama"]
    entity_type = entity["type"]
    entity_id = entity["id"]
    lcd_name = format_lcd_name(entity_name)

    save_last_tap_info(clean_uid, device_id, is_registered=True, name=entity_name, ip_addr=ip_addr)

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
            "device_id": device_id,
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

    logging.info(f"[SUCCESS] Presensi '{entity_name}' tercatat pada sesi '{sesi_name}' via '{device_id}' ({status_kehadiran}).")

    short_sesi = sesi.get("kode_sesi", "HADIR") if sesi else "HADIR"
    lcd_status = format_lcd_text(f"{short_sesi}: Hadir", 16)

    return {
        "device_id": device_id,
        "nama": lcd_name,
        "status": lcd_status
    }

# ================= MQTT CALLBACKS =================
def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        logging.info(f"Terhubung ke Broker MQTT ({MQTT_BROKER}:{MQTT_PORT})!")
        client.subscribe(MQTT_TOPIC_TAP, qos=1)
        client.subscribe(MQTT_TOPIC_HEARTBEAT, qos=1)
        logging.info(f"Berlangganan topik tap: '{MQTT_TOPIC_TAP}' & heartbeat: '{MQTT_TOPIC_HEARTBEAT}'")
    else:
        logging.error(f"Koneksi MQTT Gagal dengan kode: {rc}")

def on_message(client, userdata, msg):
    try:
        payload_raw = msg.payload.decode("utf-8").strip()
        logging.info(f"Menerima pesan [{msg.topic}]: {payload_raw}")

        try:
            data = json.loads(payload_raw)
        except json.JSONDecodeError:
            logging.warning(f"Payload bukan JSON valid: {payload_raw}")
            return

        # Prioritaskan serial unik hardware ESP32 (serial / chip_id / device_id)
        raw_device_id = data.get("serial") or data.get("chip_id") or data.get("device_id") or "ESP32_UNKNOWN"
        device_id = str(raw_device_id).strip()
        ip_addr = str(data.get("ip", "")).strip()

        # Pastikan koneksi DB aktif
        db = userdata.get("db")
        if not db or not db.is_connected():
            logging.warning("Koneksi DB terputus, mencoba reconnect...")
            db = create_db_connection()
            userdata["db"] = db

        # Jika pesan adalah HEARTBEAT / BOOT CHECK-IN dari ESP32 (tanpa perlu tap kartu)
        if msg.topic == MQTT_TOPIC_HEARTBEAT:
            save_last_device_info(device_id, ip_addr=ip_addr, event=str(data.get("event", "heartbeat")))
            if db:
                cur = db.cursor(dictionary=True)
                sync_device_presence(cur, device_id, ip_addr)
                cur.close()
            return

        uid = data.get("uid") or data.get("rfid_uid")
        if not uid:
            logging.warning("Pesan tap diterima tanpa parameter 'uid'. Diabaikan.")
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

        if not db:
            logging.error("Database offline! Tidak dapat memproses tap.")
            response_payload = {
                "device_id": device_id,
                "nama": "Server Offline",
                "status": "Coba Sesaat Lagi"
            }
        else:
            response_payload = process_tap(db, clean_uid, device_id, ip_addr)

        # Kirim balasan ke topik spesifik serial alat DAN topik umum (kompatibilitas firmware lama)
        response_json = json.dumps(response_payload)
        specific_topic = f"{MQTT_TOPIC_RESPONSE}/{device_id}"
        client.publish(specific_topic, response_json, qos=1)
        client.publish(MQTT_TOPIC_RESPONSE, response_json, qos=1)
        logging.info(f"Respons dikirim ke [{specific_topic}]: {response_json}")

    except Exception as ex:
        logging.error(f"Error memproses pesan MQTT: {ex}", exc_info=True)

# ================= RUNNER =================
def main():
    logging.info("=" * 65)
    logging.info("Memulai Multi-Location Presensi Daemon Yayasan Al Muttaqin")
    logging.info(f"Broker: {MQTT_BROKER}:{MQTT_PORT}")
    logging.info(f"Topik Sub: {MQTT_TOPIC_TAP}, {MQTT_TOPIC_HEARTBEAT}")
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
