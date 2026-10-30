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
import threading
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

def get_active_sesi(cursor, device_id: str, current_time: str, strict: bool = True):
    """
    Mencari sesi aktif di master_sesis yang cocok dengan device_id dan jam saat ini.
    Jika strict=True, hanya kembalikan sesi jika waktu sekarang benar-benar berada dalam rentang sesi.
    """
    query = """
        SELECT id, kode_sesi, nama_sesi, jam_mulai, jam_selesai, toleransi_menit, kategori, device_id_target
        FROM master_sesis
        WHERE (device_id_target = %s OR device_id_target IS NULL OR device_id_target = '')
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

    # Jika strict=False dan tidak ada sesi pada jam ini, gunakan sesi fallback pertama
    if not sesi and not strict:
        fallback_query = "SELECT id, kode_sesi, nama_sesi, jam_mulai, jam_selesai, toleransi_menit, kategori, device_id_target FROM master_sesis ORDER BY id ASC LIMIT 1"
        cursor.execute(fallback_query)
        sesi = cursor.fetchone()

    return sesi

def broadcast_device_session(client, db, device_id: str):
    """
    Mengirimkan informasi sesi aktif ke topik MQTT per-device dan global.
    Jika ada sesi aktif:
      status="aktif", ada_sesi=True, kategori="...", nama_sesi="..."
    Jika tidak ada sesi:
      status="tidak_ada_sesi", ada_sesi=False, kategori="", nama_sesi=""
    """
    try:
        cur = db.cursor(dictionary=True)
        now_time = datetime.now().strftime("%H:%M:%S")
        sesi = get_active_sesi(cur, device_id, now_time, strict=True)
        cur.close()

        if sesi:
            kat_raw = sesi.get("kategori") or "Presensi"
            kat_title = kat_raw.capitalize() if isinstance(kat_raw, str) else "Presensi"
            nama_raw = sesi.get("nama_sesi") or kat_title

            payload = {
                "status": "aktif",
                "ada_sesi": True,
                "device_id": device_id,
                "kategori": kat_title,
                "nama_sesi": nama_raw,
                "kode_sesi": sesi.get("kode_sesi", ""),
                "jam_mulai": str(sesi.get("jam_mulai", "")),
                "jam_selesai": str(sesi.get("jam_selesai", ""))
            }
        else:
            payload = {
                "status": "tidak_ada_sesi",
                "ada_sesi": False,
                "device_id": device_id,
                "kategori": "",
                "nama_sesi": "",
                "kode_sesi": ""
            }

        payload_json = json.dumps(payload)
        # 1. Topik per-device (retained agar ESP32 yang baru boot/reconnect langsung menerima)
        client.publish(f"presensi/devices/{device_id}/sesi", payload_json, qos=1, retain=True)
        client.publish(f"presensi/sesi/{device_id}", payload_json, qos=1, retain=True)
        # 2. Topik global (retained)
        client.publish("presensi/sesi_aktif", payload_json, qos=1, retain=True)
        logging.info(f"[BROADCAST SESI] {device_id} -> ada_sesi={payload['ada_sesi']}, sesi='{payload.get('nama_sesi', '-')}' ({payload.get('jam_mulai')}-{payload.get('jam_selesai')})")
    except Exception as ex:
        logging.error(f"Gagal broadcast sesi {device_id}: {ex}")

def reverse_bytes_hex(hex_str: str) -> str:
    """
    Membalik urutan byte dalam string HEX 8-digit (Big-Endian <-> Little-Endian).
    Contoh: A7F0B7D1 -> D1B7F0A7
    """
    clean_hex = str(hex_str).strip().upper()
    if len(clean_hex) != 8:
        return clean_hex
    return clean_hex[6:8] + clean_hex[4:6] + clean_hex[2:4] + clean_hex[0:2]

def get_uid_variants(raw_uid: str) -> list:
    """
    Menghasilkan semua kemungkinan varian format serial kartu RFID/NFC:
    1. Raw UID apa adanya (misal: ';42992,47057' atau '6116F1A7' atau '1628893607')
    2. Format tanpa titik koma: '42992,47057'
    3. Format angka gabungan: '4299247057'
    4. Format HEX 8-karakter uppercase & lowercase: '6116F1A7', '6116f1a7'
    5. Format desimal 32-bit: '1628893607'
    6. Varian Byte-Reversed (Big-Endian <-> Little-Endian):
       Mendukung perbedaan pembacaan antara USB Desktop Reader dan RC522 ESP32:
       - USB Reader membaca : 2817570769 (HEX: A7F0B7D1, Split: ;42992,47057)
       - ESP32 RC522 membaca: 3518492839 (HEX: D1B7F0A7, Split: ;53687,61607)
    """
    import re
    raw_str = str(raw_uid).strip().rstrip('?')
    if not raw_str:
        return []

    variants = [raw_str, raw_str.upper(), raw_str.lower()]

    # Hapus prefix titik koma atau 0x jika ada
    clean = raw_str.lstrip(';').strip()
    if clean.upper().startswith("0X"):
        clean = clean[2:].strip()

    variants.extend([clean, clean.upper(), clean.lower()])

    hex_candidates = []

    # 1. Format split desimal: ;42992,47057 atau 42992,47057
    m = re.match(r'^;?(\d{1,5}),(\d{1,5})$', raw_str)
    if m:
        w1, w2 = int(m.group(1)), int(m.group(2))
        variants.extend([f";{w1},{w2}", f"{w1},{w2}", f"{m.group(1)}{m.group(2)}"])
        h1 = f"{w1:04X}"
        h2 = f"{w2:04X}"
        hex_val = (h1 + h2).upper()
        hex_candidates.append(hex_val)

    # 2. Format HEX (misal: 6116F1A7, A7F0B7D1, D1B7F0A7)
    is_hex = bool(re.match(r'^[0-9A-Fa-f]{4,16}$', clean))
    is_pure_digits = bool(re.match(r'^\d+$', clean))

    if is_hex and (not is_pure_digits or len(clean) == 8):
        try:
            dec_from_hex = int(clean, 16)
            variants.append(str(dec_from_hex))
            if 0 <= dec_from_hex <= 0xFFFFFFFF:
                hex8 = f"{dec_from_hex:08X}".upper()
                hex_candidates.append(hex8)
        except ValueError:
            pass

    # 3. Format desimal murni (misal: 1628893607, 2817570769, 3518492839)
    if is_pure_digits:
        try:
            dec_val = int(clean)
            variants.append(str(dec_val))
            if 0 <= dec_val <= 0xFFFFFFFF:
                hex8 = f"{dec_val:08X}".upper()
                hex_candidates.append(hex8)
        except ValueError:
            pass

    # Kembangkan setiap kandidat HEX ke varian normal & byte-reversed (Big-Endian <-> Little-Endian)
    for h in list(hex_candidates):
        h_upper = h.upper().zfill(8)
        variants.extend([h_upper, h_upper.lower()])
        try:
            d_val = int(h_upper, 16)
            variants.append(str(d_val))
            w1 = (d_val >> 16) & 0xFFFF
            w2 = d_val & 0xFFFF
            variants.extend([f";{w1},{w2}", f"{w1},{w2}", f"{w1}{w2}"])
        except Exception:
            pass

        # Balik urutan 4-byte (B0 B1 B2 B3 <-> B3 B2 B1 B0)
        if len(h_upper) == 8:
            h_rev = reverse_bytes_hex(h_upper)
            variants.extend([h_rev, h_rev.lower()])
            try:
                d_rev = int(h_rev, 16)
                variants.append(str(d_rev))
                w1_rev = (d_rev >> 16) & 0xFFFF
                w2_rev = d_rev & 0xFFFF
                variants.extend([f";{w1_rev},{w2_rev}", f"{w1_rev},{w2_rev}", f"{w1_rev}{w2_rev}"])
            except Exception:
                pass

    return list(dict.fromkeys(filter(None, variants)))

def find_entity_by_uid(cursor, clean_uid: str):
    """
    Mencari entitas pemilik kartu:
    1. Cek tabel siswas (santri/siswa)
    2. Cek tabel users (guru/pegawai)
    Mendukung varian format UID: HEX (6116F1A7), desimal 32-bit (1628893607), maupun split (;24854,61863).
    """
    variants = get_uid_variants(clean_uid)
    if not variants:
        variants = [clean_uid.strip().upper()]

    logging.info(f"[SEARCH UID] Mencari pemilik kartu '{clean_uid}', varian diperiksa: {variants}")

    placeholders = ", ".join(["%s"] * len(variants))
    query_siswa = f"""
        SELECT id, nama_lengkap, kamar, status, rfid_uid 
        FROM siswas 
        WHERE UPPER(TRIM(rfid_uid)) IN ({placeholders}) 
           OR TRIM(rfid_uid) IN ({placeholders})
        LIMIT 1
    """
    cursor.execute(query_siswa, tuple(variants) + tuple(variants))
    santri = cursor.fetchone()
    if santri:
        logging.info(f"[MATCH FOUND] Kartu '{clean_uid}' cocok dengan santri: '{santri['nama_lengkap']}' (DB rfid_uid: '{santri.get('rfid_uid')}')")
        return {
            "type": "santri",
            "id": santri["id"],
            "nama": santri["nama_lengkap"],
            "sub": santri.get("kamar", "Santri")
        }

    query_user = f"""
        SELECT id, name, email, rfid_uid 
        FROM users 
        WHERE UPPER(TRIM(rfid_uid)) IN ({placeholders}) 
           OR TRIM(rfid_uid) IN ({placeholders})
        LIMIT 1
    """
    cursor.execute(query_user, tuple(variants) + tuple(variants))
    user = cursor.fetchone()
    if user:
        logging.info(f"[MATCH FOUND] Kartu '{clean_uid}' cocok dengan user: '{user['name']}' (DB rfid_uid: '{user.get('rfid_uid')}')")
        return {
            "type": "guru_pegawai",
            "id": user["id"],
            "nama": user["name"],
            "sub": "Guru/Pegawai"
        }

    logging.warning(f"[UNREGISTERED] UID '{clean_uid}' tidak ditemukan di tabel siswas maupun users (varian: {variants}).")
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
            "status": "AKSES DITOLAK",
            "kode": "DISABLED"
        }

    # 2. Pengecekan Entitas Pemilik Kartu (Santri atau Guru/Pegawai)
    entity = find_entity_by_uid(cursor, clean_uid)

    if not entity:
        cursor.close()
        logging.warning(f"[UNREGISTERED] UID '{clean_uid}' dari serial '{device_id}' tidak dikenal.")
        save_last_tap_info(clean_uid, device_id, is_registered=False, ip_addr=ip_addr)
        return {
            "device_id": device_id,
            "nama": "Tak Terdaftar",
            "status": "TIDAK TERDAFTAR",
            "kode": "UNREGISTERED"
        }

    entity_name = entity["nama"]
    entity_type = entity["type"]
    entity_id = entity["id"]
    lcd_name = format_lcd_name(entity_name)

    save_last_tap_info(clean_uid, device_id, is_registered=True, name=entity_name, ip_addr=ip_addr)

    # 3. Penentuan Sesi Dinamis (Strict: hanya sesi yang benar-benar aktif saat ini)
    sesi = get_active_sesi(cursor, device_id, current_time, strict=True)
    if not sesi:
        cursor.close()
        logging.warning(f"[NO ACTIVE SESSION] Tap dari '{entity_name}' ditolak karena tidak ada sesi aktif pada jam {current_time}.")
        return {
            "device_id": device_id,
            "nama": lcd_name,
            "status": "AKSES DITOLAK",
            "kode": "NO_SESSION"
        }

    sesi_id = sesi["id"]
    sesi_name = sesi["nama_sesi"]

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
            "status": "SUDAH ABSEN",
            "kode": "DUPLICATE",
            "sesi": sesi_name
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

    return {
        "device_id": device_id,
        "nama": lcd_name,
        "status": "BERHASIL ABSEN",
        "kode": "SUCCESS",
        "sesi": sesi_name
    }

# ================= BACKGROUND PERIODIC RUNNER =================
def periodic_session_broadcaster(client, userdata):
    """
    Secara periodik memeriksa dan mem-broadcast status sesi aktif
    ke seluruh perangkat ESP32 setiap 15 detik via MQTT.
    """
    logging.info("[BROADCASTER] Background thread broadcast sesi aktif dimulai.")
    while True:
        try:
            db = userdata.get("db") if userdata else None
            if not db or not db.is_connected():
                try:
                    db = create_db_connection()
                    if userdata:
                        userdata["db"] = db
                except Exception:
                    pass

            if db and db.is_connected():
                cur = db.cursor(dictionary=True)
                cur.execute("SELECT device_id FROM master_devices WHERE status_aktif = 1")
                rows = cur.fetchall()
                cur.close()

                target_devs = [r["device_id"] for r in rows if r.get("device_id")]
                if "tap_masjid" not in target_devs:
                    target_devs.append("tap_masjid")

                for dev_id in target_devs:
                    broadcast_device_session(client, db, dev_id)
        except Exception as err:
            logging.debug(f"Periodic broadcast loop error: {err}")
        time.sleep(15)

# ================= MQTT CALLBACKS =================
def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        logging.info(f"Terhubung ke Broker MQTT ({MQTT_BROKER}:{MQTT_PORT})!")
        # 1. Topik tap kartu
        client.subscribe(MQTT_TOPIC_TAP, qos=1)
        client.subscribe("presensi/devices/+/tap", qos=1)

        # 2. Topik heartbeat & ping status alat
        client.subscribe(MQTT_TOPIC_HEARTBEAT, qos=1)
        client.subscribe("presensi/devices/+/heartbeat", qos=1)
        client.subscribe("presensi/devices/+/ping", qos=1)

        # 3. Topik permintaan sesi aktif
        client.subscribe("presensi/get_sesi", qos=1)
        client.subscribe("presensi/devices/+/get_sesi", qos=1)

        logging.info("Berlangganan topik tap, heartbeat, ping, & get_sesi.")

        # Broadcast sesi awal untuk tap_masjid saat daemon baru terkoneksi
        db = userdata.get("db") if userdata else None
        if db:
            broadcast_device_session(client, db, "tap_masjid")
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

        # Prioritaskan device_id eksplisit yang didaftarkan (misal tap_masjid) atau serial unik hardware
        raw_device_id = data.get("device_id") or data.get("serial") or data.get("chip_id") or "tap_masjid"
        device_id = str(raw_device_id).strip()
        ip_addr = str(data.get("ip", "")).strip()

        # Pastikan koneksi DB aktif
        db = userdata.get("db")
        if not db or not db.is_connected():
            logging.warning("Koneksi DB terputus, mencoba reconnect...")
            db = create_db_connection()
            userdata["db"] = db

        # Jika pesan adalah HEARTBEAT / BOOT / PING / PERMINTAAN SESI dari ESP32
        is_heartbeat_or_sesi_req = (
            msg.topic == MQTT_TOPIC_HEARTBEAT
            or msg.topic.endswith("/heartbeat")
            or msg.topic.endswith("/ping")
            or msg.topic == "presensi/get_sesi"
            or msg.topic.endswith("/get_sesi")
        )

        if is_heartbeat_or_sesi_req:
            save_last_device_info(device_id, ip_addr=ip_addr, event=str(data.get("event", "heartbeat")))
            if db:
                cur = db.cursor(dictionary=True)
                sync_device_presence(cur, device_id, ip_addr)
                cur.close()
                # Segera balas dengan sesi aktif terbaru untuk perangkat tersebut
                broadcast_device_session(client, db, device_id)
            return

        uid = data.get("rfid_uid") or data.get("uid") or data.get("rfid_uid_rev") or data.get("uid_rev")
        if not uid:
            logging.warning("Pesan tap diterima tanpa parameter 'rfid_uid' / 'uid'. Diabaikan.")
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
                "status": "AKSES DITOLAK",
                "kode": "SERVER_OFFLINE"
            }
        else:
            response_payload = process_tap(db, clean_uid, device_id, ip_addr)

        # Kirim balasan ke topik spesifik per-device DAN topik umum
        response_json = json.dumps(response_payload)
        client.publish(f"presensi/santri/response/{device_id}", response_json, qos=1)
        client.publish(f"presensi/devices/{device_id}/response", response_json, qos=1)
        client.publish(MQTT_TOPIC_RESPONSE, response_json, qos=1)
        logging.info(f"Respons dikirim ke [presensi/santri/response/{device_id} & presensi/devices/{device_id}/response]: {response_json}")

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

    userdata = {"db": db}

    try:
        from paho.mqtt.enums import CallbackAPIVersion
        client = mqtt.Client(
            CallbackAPIVersion.VERSION2,
            client_id=MQTT_CLIENT_ID,
            userdata=userdata
        )
    except (ImportError, AttributeError):
        client = mqtt.Client(
            client_id=MQTT_CLIENT_ID,
            userdata=userdata
        )

    client.on_connect = on_connect
    client.on_message = on_message

    try:
        client.connect(MQTT_BROKER, MQTT_PORT, keepalive=60)

        # Jalankan background thread broadcast berkala sesi aktif ke ESP32
        broadcaster_thread = threading.Thread(
            target=periodic_session_broadcaster,
            args=(client, userdata),
            daemon=True
        )
        broadcaster_thread.start()
        logging.info("Background thread broadcast sesi aktif berhasil dijalankan.")

        client.loop_forever()
    except KeyboardInterrupt:
        logging.info("Daemon dihentikan oleh pengguna.")
    finally:
        if db and db.is_connected():
            db.close()
        client.disconnect()

if __name__ == "__main__":
    main()
