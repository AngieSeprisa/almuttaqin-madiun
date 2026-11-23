#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Presensi RFID Daemon v3.0 - Multi-Lokasi, WIB-Locked, Thread-Safe & Ultra-Fast Tap
Yayasan Pondok Pesantren Al Muttaqin Madiun

Perbaikan Utama v3.0:
1. Zona Waktu Wajib WIB (Asia/Jakarta / UTC+7):
   - Tidak lagi bergantung pada jam OS VPS (UTC) sehingga status sesi aktif di LCD
     100% sinkron dengan aplikasi Web Laravel.
2. Toleransi Pencocokan Device ID (Spasi / Underscore / Huruf Besar-Kecil):
   - "kantor smp", "kantor_smp", dan "Kantor-SMP" dikenali sebagai perangkat yang sama,
     mencegah LCD menampilkan "TIDAK ADA SESI" akibat perbedaan format spasi vs underscore.
3. Koneksi MySQL Terpisah Per Thread (Thread-Safe):
   - Thread broadcast sesi dan handler tap menggunakan koneksi DB masing-masing dengan
     auto-ping reconnect, mencegah error "Unread result found" yang sebelumnya memicu
     broadcast "TIDAK ADA SESI".
4. Pemrosesan Tap Ultra-Cepat (<20ms) & Anti-Timeout:
   - Cache jadwal sesi & master perangkat di memori (refresh instan saat web menyimpan sesi),
     query indeks UID cepat terlebih dahulu, respons QoS 0, dan debounce membalas cache
     sehingga ESP32 tidak pernah hang "SERVER TIMEOUT".
"""

import os
import sys
import json
import time
import re
import logging
import threading
from datetime import datetime, timedelta, timezone
from pathlib import Path

try:
    from zoneinfo import ZoneInfo
    WIB_TZ = ZoneInfo("Asia/Jakarta")
except Exception:
    WIB_TZ = timezone(timedelta(hours=7))

# Load environment variables jika file .env tersedia
try:
    from dotenv import load_dotenv
    env_path = Path(__file__).resolve().parent / ".env"
    if env_path.exists():
        load_dotenv(dotenv_path=env_path)
except ImportError:
    pass

try:
    import mysql.connector
    from mysql.connector import Error as MySQLError
except ImportError:
    mysql = None
    MySQLError = Exception

try:
    import paho.mqtt.client as mqtt
except ImportError:
    mqtt = None

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
    "charset": "utf8mb4",
    "connection_timeout": 5,
}

DEBOUNCE_SECONDS = float(os.getenv("DEBOUNCE_SECONDS", "1.5"))
SESSION_CACHE_TTL = float(os.getenv("SESSION_CACHE_TTL", "3.0"))
DEVICE_CACHE_TTL = float(os.getenv("DEVICE_CACHE_TTL", "30.0"))
BROADCAST_CHECK_INTERVAL = float(os.getenv("BROADCAST_CHECK_INTERVAL", "3.0"))
BROADCAST_KEEPALIVE_SECONDS = float(os.getenv("BROADCAST_KEEPALIVE_SECONDS", "30.0"))

# File cache untuk live auto-detect pairing kartu & serial perangkat di dashboard web
LAST_TAP_LOG_FILE = Path("/tmp/rfid_last_tap.json")
LAST_DEVICE_LOG_FILE = Path("/tmp/rfid_last_device.json")
SESI_DIRTY_SIGNAL_FILE = Path("/tmp/rfid_sesi_dirty")

# In-memory cache & state (dilindungi lock)
_state_lock = threading.Lock()
tap_history = {}           # {(norm_device_id, clean_uid): (timestamp, compact_resp_dict)}
seen_device_aliases = {}   # {norm_device_id: set(raw_device_ids)}
device_presence_cache = {} # {norm_device_id: {"row": dict, "fetched_at": float, "last_seen_db_at": float}}
session_snapshot_cache = {
    "fetched_at": 0.0,
    "dirty_mtime": 0.0,
    "sesis": [],
    "allocations_by_sesi": {},
}
last_broadcast_state = {}  # {device_id: (signature_tuple, published_at)}
_cleared_global_retained = False

# ================= LOGGING SETUP =================
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)


# ================= WAKTU & NORMALISASI =================
def now_wib() -> datetime:
    """Selalu mengembalikan waktu lokal WIB (Asia/Jakarta, UTC+7) sebagai naive datetime."""
    return datetime.now(WIB_TZ).replace(tzinfo=None)


def normalize_device_id(device_id: str) -> str:
    """Normalisasi Device ID agar perbedaan spasi, strip, dan huruf besar-kecil tetap cocok."""
    if not device_id:
        return ""
    return re.sub(r"[\s\-]+", "_", str(device_id).strip()).lower()


def register_device_alias(raw_device_id: str):
    """Catat varian mentah device_id yang pernah dikirim ESP32 agar broadcast selalu tepat sasaran."""
    raw = str(raw_device_id or "").strip()
    if not raw:
        return
    norm = normalize_device_id(raw)
    if not norm:
        return
    with _state_lock:
        aliases = seen_device_aliases.setdefault(norm, set())
        aliases.add(raw)


def get_device_publish_variants(device_id: str) -> list:
    """
    Kembalikan daftar varian topik/device_id untuk satu alat (misal: 'kantor_smp' dan 'kantor smp')
    agar firmware lama maupun baru tetap menerima pesan sesi/respons meski beda spasi vs underscore.
    """
    raw = str(device_id or "").strip()
    if not raw:
        return []
    norm = normalize_device_id(raw)
    variants = [raw]
    with _state_lock:
        for alias in sorted(seen_device_aliases.get(norm, set())):
            if alias not in variants:
                variants.append(alias)
    if "_" in raw:
        space_var = raw.replace("_", " ")
        if space_var not in variants:
            variants.append(space_var)
    if " " in raw:
        under_var = re.sub(r"\s+", "_", raw)
        if under_var not in variants:
            variants.append(under_var)
    if norm and norm not in variants:
        variants.append(norm)
    return variants


def normalize_time_to_seconds(val) -> int:
    """Konversi nilai TIME (timedelta, datetime.time, atau string 'HH:MM[:SS]') ke detik sejak 00:00:00."""
    if val is None:
        return -1
    if isinstance(val, timedelta):
        return int(val.total_seconds()) % 86400
    s = str(val).strip()
    if not s:
        return -1
    parts = s.split(":")
    try:
        hh = int(parts[0])
        mm = int(parts[1]) if len(parts) > 1 else 0
        ss = int(float(parts[2])) if len(parts) > 2 else 0
        return hh * 3600 + mm * 60 + ss
    except (ValueError, IndexError):
        return -1


def format_time_str(val) -> str:
    """Format nilai TIME DB menjadi 'HH:MM:SS'."""
    sec = normalize_time_to_seconds(val)
    if sec < 0:
        return "00:00:00"
    hh = (sec // 3600) % 24
    mm = (sec % 3600) // 60
    ss = sec % 60
    return f"{hh:02d}:{mm:02d}:{ss:02d}"


def is_time_in_session(jam_mulai, jam_selesai, current_time_str: str) -> bool:
    """
    Cek apakah current_time_str berada di dalam rentang [jam_mulai, jam_selesai].
    Jika jam_selesai berakhiran :00 (input jam:menit dari web), inklusif hingga :59
    agar 100% selaras dengan indikator 'Aktif Jam Ini' pada aplikasi web.
    """
    start_sec = normalize_time_to_seconds(jam_mulai)
    end_sec = normalize_time_to_seconds(jam_selesai)
    now_sec = normalize_time_to_seconds(current_time_str)
    if start_sec < 0 or end_sec < 0 or now_sec < 0:
        return False

    if end_sec % 60 == 0:
        end_sec = min(86399, end_sec + 59)

    if start_sec <= end_sec:
        return start_sec <= now_sec <= end_sec
    return now_sec >= start_sec or now_sec <= end_sec


def get_dirty_signal_mtime() -> float:
    try:
        if SESI_DIRTY_SIGNAL_FILE.exists():
            return SESI_DIRTY_SIGNAL_FILE.stat().st_mtime
    except Exception:
        pass
    return 0.0


# ================= DATABASE HELPERS =================
def create_db_connection(label: str = "main"):
    if mysql is None:
        logging.error("Paket mysql-connector-python tidak terpasang.")
        return None
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        logging.info(
            f"[DB:{label}] Terhubung ke database '{DB_CONFIG['database']}' "
            f"di {DB_CONFIG['host']}:{DB_CONFIG['port']}."
        )
        return conn
    except MySQLError as e:
        logging.error(f"[DB:{label}] Gagal koneksi ke database MySQL: {e}")
        return None


def ensure_db_connection(conn, label: str = "main"):
    """Pastikan koneksi MySQL benar-benar hidup menggunakan ping(reconnect=True)."""
    if conn is not None:
        try:
            conn.ping(reconnect=True, attempts=2, delay=0.5)
            return conn
        except Exception as ex:
            logging.warning(f"[DB:{label}] Ping koneksi gagal ({ex}), membuat koneksi baru...")
            try:
                conn.close()
            except Exception:
                pass
    return create_db_connection(label=label)


def format_lcd_text(text: str, max_len: int = 16) -> str:
    if not text:
        return ""
    return text.strip()[:max_len]


def format_lcd_name(full_name: str) -> str:
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


def _write_json_async(path: Path, data: dict):
    def _worker():
        try:
            path.write_text(json.dumps(data), encoding="utf-8")
        except Exception as e:
            logging.debug(f"Gagal menulis cache {path}: {e}")
    threading.Thread(target=_worker, daemon=True).start()


def save_last_tap_info(uid: str, device_id: str, is_registered: bool, name: str = "", ip_addr: str = ""):
    data = {
        "uid": uid,
        "device_id": device_id,
        "ip": ip_addr,
        "is_registered": is_registered,
        "name": name,
        "timestamp": now_wib().strftime("%Y-%m-%d %H:%M:%S")
    }
    _write_json_async(LAST_TAP_LOG_FILE, data)


def save_last_device_info(device_id: str, ip_addr: str = "", event: str = "online"):
    data = {
        "device_id": device_id,
        "ip": ip_addr,
        "event": event,
        "timestamp": now_wib().strftime("%Y-%m-%d %H:%M:%S")
    }
    _write_json_async(LAST_DEVICE_LOG_FILE, data)


def sync_device_presence(cursor, device_id: str, ip_addr: str = "", force_db_update: bool = False) -> dict:
    """
    Memastikan perangkat terdaftar di master_devices (mencocokkan exact maupun normalisasi
    spasi/underscore agar tidak tercipta baris duplikat) dan meng-cache status di memori
    agar tap kartu tidak terbebani query UPDATE setiap detik.
    """
    raw_dev = str(device_id or "").strip()
    norm_dev = normalize_device_id(raw_dev)
    register_device_alias(raw_dev)

    now_ts = time.time()
    if not force_db_update and norm_dev:
        with _state_lock:
            cached = device_presence_cache.get(norm_dev)
            if cached and (now_ts - cached["fetched_at"]) < DEVICE_CACHE_TTL:
                return dict(cached["row"])

    cursor.execute(
        """
        SELECT id, device_id, nama_lokasi, status_aktif
        FROM master_devices
        WHERE device_id = %s
           OR LOWER(REPLACE(REPLACE(TRIM(device_id), ' ', '_'), '-', '_')) = %s
        ORDER BY (device_id = %s) DESC, id ASC
        LIMIT 1
        """,
        (raw_dev, norm_dev, raw_dev)
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
                (raw_dev, default_label, ip_addr or None)
            )
        except Exception:
            cursor.execute(
                """
                INSERT INTO master_devices (device_id, nama_lokasi, kategori_area, status_aktif, created_at, updated_at)
                VALUES (%s, %s, 'pondok', 1, NOW(), NOW())
                """,
                (raw_dev, default_label)
            )
        logging.info(f"[AUTO-DISCOVERY] Serial perangkat baru '{raw_dev}' ({ip_addr or '-'}) otomatis terdaftar.")
        device = {
            "device_id": raw_dev,
            "nama_lokasi": default_label,
            "status_aktif": 1
        }
    else:
        db_dev_id = device["device_id"]
        register_device_alias(db_dev_id)
        try:
            if ip_addr:
                cursor.execute(
                    "UPDATE master_devices SET last_seen_at = NOW(), ip_address = %s, updated_at = NOW() WHERE device_id = %s",
                    (ip_addr, db_dev_id)
                )
            else:
                cursor.execute(
                    "UPDATE master_devices SET last_seen_at = NOW(), updated_at = NOW() WHERE device_id = %s",
                    (db_dev_id,)
                )
        except Exception:
            pass

    if norm_dev:
        with _state_lock:
            device_presence_cache[norm_dev] = {
                "row": dict(device),
                "fetched_at": now_ts,
                "last_seen_db_at": now_ts,
            }

    return device


# ================= PEMUATAN & RESOLUSI SESI AKTIF =================
def load_session_snapshot(cursor, force_refresh: bool = False) -> tuple:
    """
    Memuat snapshot master_sesis dan sesi_device_allocations dari DB dengan cache 3 detik
    (langsung di-refresh bila file sinyal /tmp/rfid_sesi_dirty berubah dari aplikasi Web).
    """
    now_ts = time.time()
    dirty_mtime = get_dirty_signal_mtime()

    with _state_lock:
        cache_valid = (
            not force_refresh
            and session_snapshot_cache["fetched_at"] > 0
            and (now_ts - session_snapshot_cache["fetched_at"]) < SESSION_CACHE_TTL
            and dirty_mtime <= session_snapshot_cache["dirty_mtime"]
        )
        if cache_valid:
            return (
                session_snapshot_cache["sesis"],
                session_snapshot_cache["allocations_by_sesi"],
            )

    cursor.execute("SELECT * FROM master_sesis")
    sesis = cursor.fetchall() or []

    allocations_by_sesi = {}
    try:
        cursor.execute("SELECT * FROM sesi_device_allocations")
        allocs = cursor.fetchall() or []
        for a in allocs:
            sid = a.get("master_sesi_id")
            allocations_by_sesi.setdefault(sid, []).append(a)
    except Exception as ex_alloc:
        logging.warning(f"[SESI ALLOC QUERY WARNING] {ex_alloc}")
        allocations_by_sesi = {}

    with _state_lock:
        session_snapshot_cache["fetched_at"] = now_ts
        session_snapshot_cache["dirty_mtime"] = dirty_mtime
        session_snapshot_cache["sesis"] = sesis
        session_snapshot_cache["allocations_by_sesi"] = allocations_by_sesi

    return sesis, allocations_by_sesi


def _sort_key_updated_id(row: dict) -> tuple:
    upd = row.get("updated_at")
    upd_str = str(upd) if upd is not None else ""
    try:
        row_id = int(row.get("id") or 0)
    except (TypeError, ValueError):
        row_id = 0
    return (upd_str, row_id)


def resolve_active_sesi_from_snapshot(
    sesis: list,
    allocations_by_sesi: dict,
    device_id: str,
    current_time: str,
    strict: bool = True,
):
    """
    Menentukan sesi aktif murni dari snapshot data (100% deterministik & bebas bug SQL):
    1. TAHAP 1 (Exact / Normalized Device Match di sesi_device_allocations):
       Cocokkan device_id secara toleran (spasi/underscore/case-insensitive).
    2. TAHAP 2 (Global Allocation di sesi_device_allocations):
       device_id IS NULL / '' / 'ALL' / 'semua'.
    3. TAHAP 3 (Legacy Fallback di master_sesis.device_id_target):
       Untuk sesi yang tidak memiliki baris di sesi_device_allocations atau hanya memakai kolom legacy.
    """
    norm_dev = normalize_device_id(device_id)
    active_sesis = [
        s for s in sesis
        if is_time_in_session(s.get("jam_mulai"), s.get("jam_selesai"), current_time)
    ]

    exact_candidates = []
    global_candidates = []
    legacy_candidates = []

    for s in active_sesis:
        sid = s.get("id")
        allocs = allocations_by_sesi.get(sid, [])

        if allocs:
            for a in allocs:
                a_dev_raw = str(a.get("device_id") or "").strip()
                a_dev_norm = normalize_device_id(a_dev_raw)
                merged = dict(s)
                merged["allocation_id"] = a.get("id")
                merged["alloc_target_kategori"] = a.get("target_kategori")
                merged["alloc_target_filter"] = a.get("target_filter")
                merged["alloc_target_gender"] = a.get("target_gender")
                merged["alloc_device_id"] = a.get("device_id")

                gender_spec = 1 if str(a.get("target_gender") or "").upper() in ("L", "P") else 0
                kat_lower = str(a.get("target_kategori") or "").strip().lower()
                kat_spec = 1 if kat_lower not in ("", "semua", "semua_santri") else 0
                upd_str, row_id = _sort_key_updated_id(s)

                if a_dev_norm and a_dev_norm not in ("all", "semua"):
                    if norm_dev and a_dev_norm == norm_dev:
                        exact_candidates.append(((gender_spec, kat_spec, upd_str, row_id), merged))
                else:
                    global_candidates.append(((gender_spec, upd_str, row_id), merged))
        else:
            dev_target_raw = str(s.get("device_id_target") or "").strip()
            dev_target_norm = normalize_device_id(dev_target_raw)
            is_global_legacy = dev_target_norm in ("", "all", "semua")
            is_match_legacy = bool(norm_dev and dev_target_norm == norm_dev)
            if is_match_legacy or is_global_legacy:
                upd_str, row_id = _sort_key_updated_id(s)
                legacy_candidates.append(((1 if is_match_legacy else 0, upd_str, row_id), dict(s)))

    if exact_candidates:
        exact_candidates.sort(key=lambda item: item[0], reverse=True)
        return exact_candidates[0][1]

    if global_candidates:
        global_candidates.sort(key=lambda item: item[0], reverse=True)
        return global_candidates[0][1]

    if legacy_candidates:
        legacy_candidates.sort(key=lambda item: item[0], reverse=True)
        return legacy_candidates[0][1]

    if not strict and sesis:
        sorted_all = sorted(sesis, key=_sort_key_updated_id, reverse=True)
        return dict(sorted_all[0])

    return None


def get_active_sesi(cursor, device_id: str, current_time: str, strict: bool = True, force_refresh: bool = False):
    sesis, allocations_by_sesi = load_session_snapshot(cursor, force_refresh=force_refresh)
    return resolve_active_sesi_from_snapshot(
        sesis,
        allocations_by_sesi,
        device_id=device_id,
        current_time=current_time,
        strict=strict,
    )


def build_compact_sesi_payload(device_id: str, sesi: dict = None) -> tuple:
    """Membangun payload JSON ringkas (< 170 byte) agar selalu muat di buffer PubSubClient ESP32."""
    dev_str = str(device_id).strip()
    if sesi:
        kat_raw = str(sesi.get("kategori") or "Pengajian").strip()
        kategori_name = kat_raw.capitalize() if kat_raw else "Pengajian"
        sesi_name = str(sesi.get("nama_sesi") or kategori_name).strip()

        payload = {
            "device_id": dev_str,
            "serial": dev_str,
            "ada_sesi": True,
            "status": "aktif",
            "nama_sesi": sesi_name[:16],
            "sesi": sesi_name[:16],
            "kategori": kategori_name[:16],
        }
        meta = (sesi.get("alloc_target_kategori") or sesi.get("target_kategori") or "", sesi.get("alloc_target_gender", "ALL"))
    else:
        payload = {
            "device_id": dev_str,
            "serial": dev_str,
            "ada_sesi": False,
            "status": "tidak_ada_sesi",
            "nama_sesi": "",
            "sesi": "",
            "kategori": "",
        }
        meta = ("", "ALL")

    payload_json = json.dumps(payload, separators=(",", ":"))
    return payload, payload_json, meta


def publish_compact_sesi(client, device_id: str, sesi: dict = None, publish_global_legacy: bool = False):
    """
    Mengirimkan payload sesi aktif ke seluruh varian topik device_id (misal 'kantor_smp' DAN 'kantor smp')
    dengan `device_id` di dalam payload JSON yang sesuai topik tersebut agar firmware lama maupun baru cocok 100%.
    """
    variants = get_device_publish_variants(device_id)
    if not variants:
        return

    for idx, dev_var in enumerate(variants):
        payload, payload_json, meta = build_compact_sesi_payload(dev_var, sesi)
        client.publish(f"presensi/devices/{dev_var}/sesi", payload_json, qos=0, retain=True)
        client.publish(f"presensi/sesi/{dev_var}", payload_json, qos=0, retain=True)
        client.publish(f"rfid/{dev_var}/sesi", payload_json, qos=0, retain=True)
        client.publish("presensi/santri/status_sesi", payload_json, qos=0, retain=False)
        client.publish("rfid/presensi/status_sesi", payload_json, qos=0, retain=False)

        if publish_global_legacy and idx == 0:
            client.publish("presensi/sesi_aktif", payload_json, qos=0, retain=True)

        if idx == 0:
            logging.info(
                f"[BROADCAST SESI] {dev_var} (variants={variants}) -> ada_sesi={payload['ada_sesi']}, "
                f"sesi='{payload.get('nama_sesi') or '-'}', wib={now_wib().strftime('%H:%M:%S')} "
                f"(Target: {meta[0]}, Gender: {meta[1]})"
            )


def broadcast_device_session(client, db, device_id: str, publish_global_legacy: bool = False, force_refresh: bool = True):
    if not device_id:
        return
    try:
        cur = db.cursor(dictionary=True)
        now_time = now_wib().strftime("%H:%M:%S")
        sesi = get_active_sesi(cur, device_id, now_time, strict=True, force_refresh=force_refresh)
        cur.close()
        publish_compact_sesi(client, device_id, sesi, publish_global_legacy=publish_global_legacy)
    except Exception as ex:
        logging.warning(f"Gagal broadcast sesi {device_id}: {ex}")


def broadcast_all_devices(client, db, force_publish: bool = False):
    """
    Memeriksa seluruh perangkat terdaftar & perangkat yang baru mengirim heartbeat,
    lalu mem-broadcast ke MQTT apabila sesi berubah atau interval keepalive tercapai.
    """
    global _cleared_global_retained

    cur = db.cursor(dictionary=True)
    sesis, allocations_by_sesi = load_session_snapshot(cur, force_refresh=True)

    cur.execute("SELECT device_id FROM master_devices WHERE status_aktif = 1")
    rows = cur.fetchall() or []
    cur.close()

    # Kelompokkan perangkat berdasarkan normalized_id agar tidak ada duplikasi broadcast
    canonical_by_norm = {}
    for r in rows:
        dev = str(r.get("device_id") or "").strip()
        if dev:
            register_device_alias(dev)
            canonical_by_norm.setdefault(normalize_device_id(dev), dev)

    for alloc_list in allocations_by_sesi.values():
        for a in alloc_list:
            dev = str(a.get("device_id") or "").strip()
            norm = normalize_device_id(dev)
            if norm and norm not in ("all", "semua"):
                register_device_alias(dev)
                canonical_by_norm.setdefault(norm, dev)

    with _state_lock:
        for norm, aliases in seen_device_aliases.items():
            if norm not in canonical_by_norm and aliases:
                canonical_by_norm[norm] = sorted(aliases)[0]

    now_time = now_wib().strftime("%H:%M:%S")
    now_ts = time.time()
    prepared = []
    unique_signatures = set()

    for norm_id in sorted(canonical_by_norm.keys()):
        dev_id = canonical_by_norm[norm_id]
        sesi = resolve_active_sesi_from_snapshot(sesis, allocations_by_sesi, dev_id, now_time, strict=True)
        payload, _, _ = build_compact_sesi_payload(dev_id, sesi)
        sig = (payload["ada_sesi"], payload["kategori"], payload["nama_sesi"])
        prepared.append((norm_id, dev_id, sesi, sig))
        unique_signatures.add(sig)

    if not prepared:
        return

    is_uniform = (len(unique_signatures) == 1)
    if not is_uniform and not _cleared_global_retained:
        try:
            client.publish("presensi/sesi_aktif", "", qos=0, retain=True)
            _cleared_global_retained = True
        except Exception:
            pass
    elif is_uniform:
        _cleared_global_retained = False

    for idx, (norm_id, dev_id, sesi, sig) in enumerate(prepared):
        prev = last_broadcast_state.get(norm_id)
        need_publish = (
            force_publish
            or prev is None
            or prev[0] != sig
            or (now_ts - prev[1]) >= BROADCAST_KEEPALIVE_SECONDS
        )
        if not need_publish:
            continue

        publish_global = is_uniform and (idx == len(prepared) - 1)
        publish_compact_sesi(client, dev_id, sesi, publish_global_legacy=publish_global)
        last_broadcast_state[norm_id] = (sig, now_ts)


# ================= UID VARIANTS & ENTITY LOOKUP =================
def reverse_bytes_hex(hex_str: str) -> str:
    clean_hex = str(hex_str).strip().upper()
    if len(clean_hex) != 8:
        return clean_hex
    return clean_hex[6:8] + clean_hex[4:6] + clean_hex[2:4] + clean_hex[0:2]


def get_uid_variants(raw_uid: str) -> list:
    raw_str = str(raw_uid).strip().rstrip("?")
    if not raw_str:
        return []

    variants = [raw_str, raw_str.upper(), raw_str.lower()]

    clean = raw_str.lstrip(";").strip()
    if clean.upper().startswith("0X"):
        clean = clean[2:].strip()

    variants.extend([clean, clean.upper(), clean.lower()])

    if re.match(r"^\d+$", clean):
        stripped = clean.lstrip("0")
        if stripped:
            variants.append(stripped)
            variants.append(stripped.zfill(10))
            variants.append(stripped.zfill(9))
            variants.append(stripped.zfill(8))
        else:
            variants.append("0")
            variants.append("0".zfill(10))

    hex_candidates = []

    # 1. Format split desimal: ;42992,47057 atau 42992,47057
    m = re.match(r"^;?(\d{1,5}),(\d{1,5})$", raw_str)
    if m:
        w1, w2 = int(m.group(1)), int(m.group(2))
        variants.extend([f";{w1},{w2}", f"{w1},{w2}", f"{m.group(1)}{m.group(2)}"])
        hex_candidates.append(f"{w1:04X}{w2:04X}".upper())

    # 2. Format HEX
    is_hex = bool(re.match(r"^[0-9A-Fa-f]{4,16}$", clean))
    is_pure_digits = bool(re.match(r"^\d+$", clean))

    if is_hex and (not is_pure_digits or len(clean) == 8):
        try:
            dec_from_hex = int(clean, 16)
            dec_hex_str = str(dec_from_hex)
            variants.extend([
                dec_hex_str,
                f"{dec_from_hex:010d}",
                f"{dec_from_hex:09d}",
                f"{dec_from_hex:08d}",
                dec_hex_str.lstrip("0"),
            ])
            if 0 <= dec_from_hex <= 0xFFFFFFFF:
                hex_candidates.append(f"{dec_from_hex:08X}".upper())
        except ValueError:
            pass

    # 3. Format desimal murni
    if is_pure_digits:
        try:
            dec_val = int(clean)
            dec_str = str(dec_val)
            variants.extend([
                dec_str,
                f"{dec_val:010d}",
                f"{dec_val:09d}",
                f"{dec_val:08d}",
                dec_str.lstrip("0"),
            ])
            if 0 <= dec_val <= 0xFFFFFFFF:
                hex_candidates.append(f"{dec_val:08X}".upper())
        except ValueError:
            pass

    for h in list(hex_candidates):
        h_upper = h.upper().zfill(8)
        variants.extend([h_upper, h_upper.lower()])
        try:
            d_val = int(h_upper, 16)
            d_str = str(d_val)
            variants.extend([
                d_str,
                f"{d_val:010d}",
                f"{d_val:09d}",
                f"{d_val:08d}",
                d_str.lstrip("0"),
            ])
            w1 = (d_val >> 16) & 0xFFFF
            w2 = d_val & 0xFFFF
            variants.extend([f";{w1},{w2}", f"{w1},{w2}", f"{w1}{w2}"])
        except Exception:
            pass

        if len(h_upper) == 8:
            h_rev = reverse_bytes_hex(h_upper)
            variants.extend([h_rev, h_rev.lower()])
            try:
                d_rev = int(h_rev, 16)
                d_rev_str = str(d_rev)
                variants.extend([
                    d_rev_str,
                    f"{d_rev:010d}",
                    f"{d_rev:09d}",
                    f"{d_rev:08d}",
                    d_rev_str.lstrip("0"),
                ])
                w1_rev = (d_rev >> 16) & 0xFFFF
                w2_rev = d_rev & 0xFFFF
                variants.extend([f";{w1_rev},{w2_rev}", f"{w1_rev},{w2_rev}", f"{w1_rev}{w2_rev}"])
            except Exception:
                pass

    return list(dict.fromkeys(filter(None, variants)))


def normalize_gender(val) -> str:
    if not val:
        return ""
    v = str(val).strip().upper()
    if v in ["L", "LAKI-LAKI", "LAKI - LAKI", "PUTRA", "IKHWAN", "MALE"]:
        return "L"
    if v in ["P", "PEREMPUAN", "PUTRI", "AKHWAT", "FEMALE"]:
        return "P"
    return v


_SISWA_COLUMNS_CACHE = None


def get_siswa_columns(cursor) -> set:
    global _SISWA_COLUMNS_CACHE
    if _SISWA_COLUMNS_CACHE is not None:
        return _SISWA_COLUMNS_CACHE
    try:
        cursor.execute("SHOW COLUMNS FROM siswas")
        rows = cursor.fetchall()
        _SISWA_COLUMNS_CACHE = {r["Field"].lower() for r in rows}
    except Exception:
        _SISWA_COLUMNS_CACHE = set()
    return _SISWA_COLUMNS_CACHE


def normalize_room_name(name: str) -> str:
    if not name:
        return ""
    s = str(name).strip().lower()
    s = re.sub(r"\b(kamar|room)\b", "", s, flags=re.IGNORECASE)
    return " ".join(s.split())


def is_target_room_matched(santri_kamar: str, target_filter: str) -> bool:
    if not target_filter:
        return True
    tf_clean = str(target_filter).strip()
    if tf_clean.lower() in ["", "all", "semua", "semua santri", "semua_santri", "-"]:
        return True
    sk_clean = str(santri_kamar or "").strip()
    if not sk_clean:
        return False
    if sk_clean.lower() == tf_clean.lower():
        return True

    norm_santri = normalize_room_name(sk_clean)
    target_items = [t.strip() for t in re.split(r"[,;]+", tf_clean) if t.strip()]
    for item in target_items:
        if item.lower() in ["all", "semua", "semua santri", "semua_santri"]:
            return True
        if sk_clean.lower() == item.lower():
            return True
        norm_item = normalize_room_name(item)
        if norm_santri == norm_item:
            return True
        if norm_santri and norm_item and (norm_santri in norm_item or norm_item in norm_santri):
            return True
    return False


def _format_santri_entity(santri: dict, clean_uid: str) -> dict:
    raw_gender = santri.get("jenis_kelamin") or ""
    norm_gender = normalize_gender(raw_gender)
    if not norm_gender and santri.get("kamar"):
        kmr_lower = str(santri.get("kamar")).lower()
        if any(kw in kmr_lower for kw in ["putri", "banat", "khadijah", "aisyah", "fathimah", "fatimah", "pi"]):
            norm_gender = "P"
        elif any(kw in kmr_lower for kw in ["putra", "banin", "abu bakar", "umar", "utsman", "ali", "pa"]):
            norm_gender = "L"

    final_gender = norm_gender or raw_gender
    logging.info(f"[MATCH FOUND] Kartu '{clean_uid}' -> santri '{santri['nama_lengkap']}' (Gender: {final_gender})")
    return {
        "type": "santri",
        "id": santri["id"],
        "nama": santri["nama_lengkap"],
        "sub": santri.get("kamar") or "Santri",
        "kamar": santri.get("kamar") or "",
        "jenjang": santri.get("jenjang") or "",
        "jenis_kelamin": final_gender,
        "kelas_pengajian": santri.get("nama_kelas_pengajian") or ""
    }


def find_entity_by_uid(cursor, clean_uid: str, all_candidates: list = None):
    """
    Mencari pemilik kartu (Santri / Guru):
    1. FAST PATH: Query langsung `WHERE rfid_uid IN (...)` memanfaatkan indeks B-Tree (<2ms).
    2. FALLBACK PATH: Query `UPPER(TRIM(...))` & `TRIM(LEADING '0' ...)` hanya jika Fast Path meleset.
    """
    variants = []
    if all_candidates:
        for c in all_candidates:
            if c:
                variants.extend(get_uid_variants(str(c)))
    if clean_uid:
        variants.extend(get_uid_variants(clean_uid))

    variants = list(dict.fromkeys(filter(None, variants)))
    if not variants:
        variants = [clean_uid.strip().upper()]

    cols = get_siswa_columns(cursor)
    jk_col = "s.jenis_kelamin" if "jenis_kelamin" in cols else ("s.gender" if "gender" in cols else "NULL")
    placeholders = ", ".join(["%s"] * len(variants))

    # --- FAST PATH (INDEXED) ---
    try:
        cursor.execute(
            f"""
            SELECT s.id, s.nama_lengkap, s.kamar, s.jenjang, s.status, s.rfid_uid, s.kelas_pengajian_id,
                   COALESCE({jk_col}, p.jenis_kelamin, '') as jenis_kelamin,
                   kp.nama_kelas as nama_kelas_pengajian
            FROM siswas s
            LEFT JOIN pendaftarans p ON s.pendaftaran_id = p.id
            LEFT JOIN master_kelas_pengajians kp ON s.kelas_pengajian_id = kp.id
            WHERE s.rfid_uid IN ({placeholders})
            LIMIT 1
            """,
            tuple(variants)
        )
        santri = cursor.fetchone()
        if santri:
            return _format_santri_entity(santri, clean_uid)
    except Exception:
        pass

    try:
        cursor.execute(
            f"SELECT id, name, email, rfid_uid, primary_lembaga_id FROM users WHERE rfid_uid IN ({placeholders}) LIMIT 1",
            tuple(variants)
        )
        user = cursor.fetchone()
        if user:
            logging.info(f"[MATCH FOUND] Kartu '{clean_uid}' -> user '{user['name']}'")
            return {
                "type": "guru_pegawai",
                "id": user["id"],
                "nama": user["name"],
                "sub": "Guru/Pegawai",
                "primary_lembaga_id": user.get("primary_lembaga_id")
            }
    except Exception:
        pass

    # --- FALLBACK PATH (TRIM / LEADING ZERO) ---
    stripped_digits = list(dict.fromkeys([
        v.lstrip("0") for v in variants if v.isdigit() and v.lstrip("0")
    ]))

    stripped_clause = ""
    stripped_params = []
    if stripped_digits:
        s_placeholders = ", ".join(["%s"] * len(stripped_digits))
        stripped_clause = f" OR TRIM(LEADING '0' FROM TRIM(s.rfid_uid)) IN ({s_placeholders})"
        stripped_params = stripped_digits

    params_siswa = tuple(variants) + tuple(variants) + tuple(stripped_params)
    santri = None
    try:
        cursor.execute(
            f"""
            SELECT s.id, s.nama_lengkap, s.kamar, s.jenjang, s.status, s.rfid_uid, s.kelas_pengajian_id,
                   COALESCE({jk_col}, p.jenis_kelamin, '') as jenis_kelamin,
                   kp.nama_kelas as nama_kelas_pengajian
            FROM siswas s
            LEFT JOIN pendaftarans p ON s.pendaftaran_id = p.id
            LEFT JOIN master_kelas_pengajians kp ON s.kelas_pengajian_id = kp.id
            WHERE UPPER(TRIM(s.rfid_uid)) IN ({placeholders})
               OR TRIM(s.rfid_uid) IN ({placeholders})
               {stripped_clause}
            LIMIT 1
            """,
            params_siswa
        )
        santri = cursor.fetchone()
    except Exception:
        try:
            minimal_stripped = f" OR TRIM(LEADING '0' FROM TRIM(rfid_uid)) IN ({s_placeholders})" if stripped_digits else ""
            cursor.execute(
                f"""
                SELECT id, nama_lengkap, kamar, jenjang, status, rfid_uid
                FROM siswas
                WHERE UPPER(TRIM(rfid_uid)) IN ({placeholders})
                   OR TRIM(rfid_uid) IN ({placeholders})
                   {minimal_stripped}
                LIMIT 1
                """,
                params_siswa
            )
            santri = cursor.fetchone()
        except Exception:
            santri = None

    if santri:
        return _format_santri_entity(santri, clean_uid)

    user_stripped_clause = ""
    user_stripped_params = []
    if stripped_digits:
        u_placeholders = ", ".join(["%s"] * len(stripped_digits))
        user_stripped_clause = f" OR TRIM(LEADING '0' FROM TRIM(rfid_uid)) IN ({u_placeholders})"
        user_stripped_params = stripped_digits

    cursor.execute(
        f"""
        SELECT id, name, email, rfid_uid, primary_lembaga_id
        FROM users
        WHERE UPPER(TRIM(rfid_uid)) IN ({placeholders})
           OR TRIM(rfid_uid) IN ({placeholders})
           {user_stripped_clause}
        LIMIT 1
        """,
        tuple(variants) + tuple(variants) + tuple(user_stripped_params)
    )
    user = cursor.fetchone()
    if user:
        logging.info(f"[MATCH FOUND] Kartu '{clean_uid}' -> user '{user['name']}'")
        return {
            "type": "guru_pegawai",
            "id": user["id"],
            "nama": user["name"],
            "sub": "Guru/Pegawai",
            "primary_lembaga_id": user.get("primary_lembaga_id")
        }

    logging.warning(f"[UNREGISTERED] UID '{clean_uid}' tidak ditemukan di tabel siswas maupun users.")
    return None


def process_tap(db, uid: str, device_id: str, ip_addr: str = "", all_candidates: list = None) -> dict:
    clean_uid = uid.strip().upper()
    now = now_wib()
    today_date = now.strftime("%Y-%m-%d")
    current_time = now.strftime("%H:%M:%S")
    now_full = now.strftime("%Y-%m-%d %H:%M:%S")

    cursor = db.cursor(dictionary=True)

    # 1. Sinkronisasi Serial Device (via cache cepat 30 detik) & Cek Status Aktif
    device = sync_device_presence(cursor, device_id, ip_addr, force_db_update=False)
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
    entity = find_entity_by_uid(cursor, clean_uid, all_candidates=all_candidates)
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

    # 2.5 Khusus Guru & Pegawai: Presensi Tap Mandiri (Masuk & Pulang Otomatis)
    if entity_type in ["guru_pegawai", "user"]:
        sesi = get_active_sesi(cursor, device_id, current_time, strict=False)
        sesi_id = sesi["id"] if sesi else None
        sesi_name = sesi["nama_sesi"] if sesi else "Presensi Pegawai"

        try:
            lembaga_id = entity.get("primary_lembaga_id")
            if not lembaga_id:
                cursor.execute("SELECT primary_lembaga_id FROM users WHERE id = %s", (entity_id,))
                u_row = cursor.fetchone()
                lembaga_id = u_row["primary_lembaga_id"] if u_row and u_row.get("primary_lembaga_id") else None

            if not lembaga_id:
                dev_id_lower = str(device_id).lower()
                if "smp" in dev_id_lower:
                    cursor.execute("SELECT id FROM lembagas WHERE LOWER(kode) = 'smp' OR UPPER(singkatan) = 'SMP' LIMIT 1")
                    l_smp = cursor.fetchone()
                    if l_smp:
                        lembaga_id = l_smp["id"]
                elif "sma" in dev_id_lower:
                    cursor.execute("SELECT id FROM lembagas WHERE LOWER(kode) = 'sma' OR UPPER(singkatan) = 'SMA' LIMIT 1")
                    l_sma = cursor.fetchone()
                    if l_sma:
                        lembaga_id = l_sma["id"]

            if not lembaga_id:
                cursor.execute("SELECT lembaga_id FROM user_lembagas WHERE user_id = %s LIMIT 1", (entity_id,))
                ul_row = cursor.fetchone()
                lembaga_id = ul_row["lembaga_id"] if ul_row and ul_row.get("lembaga_id") else 1

            cursor.execute(
                """
                SELECT jam_masuk, toleransi_terlambat_menit, jam_pulang
                FROM pengaturan_absensis
                WHERE lembaga_id = %s AND is_active = 1
                LIMIT 1
                """,
                (lembaga_id,)
            )
            setting = cursor.fetchone()

            status_guru = "hadir"
            if setting and setting.get("jam_masuk"):
                try:
                    jm_str = format_time_str(setting["jam_masuk"])
                    jm_dt = datetime.strptime(f"{today_date} {jm_str}", "%Y-%m-%d %H:%M:%S")
                    tol = int(setting.get("toleransi_terlambat_menit") or 0)
                    if now > (jm_dt + timedelta(minutes=tol)):
                        status_guru = "terlambat"
                except Exception as ex_time:
                    logging.warning(f"Error parsing jam setting: {ex_time}")

            cursor.execute(
                """
                SELECT id, lembaga_id, jam_masuk, jam_pulang
                FROM presensi_gurus
                WHERE user_id = %s AND tanggal = %s
                ORDER BY id DESC LIMIT 1
                """,
                (entity_id, today_date)
            )
            p_guru = cursor.fetchone()

            if not p_guru:
                try:
                    cursor.execute(
                        """
                        SELECT id, nama_sesi FROM master_sesis
                        WHERE (kategori = 'pegawai' OR target_kategori = 'pegawai')
                          AND (kode_sesi LIKE '%MASUK%' OR LOWER(nama_sesi) LIKE '%masuk%' OR LOWER(nama_sesi) LIKE '%datang%')
                        ORDER BY (jam_mulai <= %s AND jam_selesai >= %s) DESC, id ASC
                        LIMIT 1
                        """,
                        (current_time, current_time)
                    )
                    sesi_masuk_row = cursor.fetchone()
                    if sesi_masuk_row:
                        sesi_id = sesi_masuk_row["id"]
                        sesi_name = sesi_masuk_row["nama_sesi"]
                except Exception:
                    pass

                cursor.execute(
                    """
                    INSERT INTO presensi_gurus (user_id, lembaga_id, tanggal, jam_masuk, status, metode_masuk, created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, 'rfid_tap', NOW(), NOW())
                    """,
                    (entity_id, lembaga_id, today_date, current_time, status_guru)
                )
                cursor.execute(
                    """
                    INSERT INTO presensis (device_id, sesi_id, tipe_entitas, entitas_id, rfid_uid, waktu_tap, status_kehadiran, created_at, updated_at)
                    VALUES (%s, %s, 'guru_pegawai', %s, %s, %s, %s, NOW(), NOW())
                    """,
                    (device_id, sesi_id, entity_id, clean_uid, now_full, status_guru)
                )
                cursor.close()
                logging.info(f"[SUCCESS GURU MASUK] '{entity_name}' pukul {current_time} WIB ({status_guru}) via '{device_id}'.")
                return {
                    "device_id": device_id,
                    "nama": lcd_name,
                    "status": "HADIR MASUK",
                    "pesan": "HADIR MASUK",
                    "kode": "SUCCESS",
                    "sesi": sesi_name
                }
            else:
                last_time_val = p_guru.get("jam_pulang") or p_guru.get("jam_masuk")
                if last_time_val:
                    try:
                        lt_str = format_time_str(last_time_val)
                        last_dt = datetime.strptime(f"{today_date} {lt_str}", "%Y-%m-%d %H:%M:%S")
                        if (now - last_dt).total_seconds() < 180:
                            cursor.close()
                            logging.info(f"[DUPLICATE GURU] '{entity_name}' baru saja tap < 3 menit lalu.")
                            return {
                                "device_id": device_id,
                                "nama": lcd_name,
                                "status": "SUDAH ABSEN",
                                "pesan": "SUDAH ABSEN",
                                "kode": "DUPLICATE",
                                "sesi": sesi_name
                            }
                    except Exception as ex_dbnc:
                        logging.warning(f"Error debounce check: {ex_dbnc}")

                try:
                    cursor.execute(
                        """
                        SELECT id, nama_sesi FROM master_sesis
                        WHERE (kategori = 'pegawai' OR target_kategori = 'pegawai')
                          AND (kode_sesi LIKE '%PULANG%' OR LOWER(nama_sesi) LIKE '%pulang%')
                        ORDER BY (jam_mulai <= %s AND jam_selesai >= %s) DESC, id ASC
                        LIMIT 1
                        """,
                        (current_time, current_time)
                    )
                    sesi_pulang_row = cursor.fetchone()
                    if sesi_pulang_row:
                        sesi_id = sesi_pulang_row["id"]
                        sesi_name = sesi_pulang_row["nama_sesi"]
                except Exception:
                    pass

                cursor.execute(
                    """
                    UPDATE presensi_gurus
                    SET jam_pulang = %s, metode_pulang = 'rfid_tap', updated_at = NOW()
                    WHERE id = %s
                    """,
                    (current_time, p_guru["id"])
                )
                cursor.execute(
                    """
                    INSERT INTO presensis (device_id, sesi_id, tipe_entitas, entitas_id, rfid_uid, waktu_tap, status_kehadiran, created_at, updated_at)
                    VALUES (%s, %s, 'guru_pegawai', %s, %s, %s, 'pulang', NOW(), NOW())
                    """,
                    (device_id, sesi_id, entity_id, clean_uid, now_full)
                )
                cursor.close()
                logging.info(f"[SUCCESS GURU PULANG] '{entity_name}' pukul {current_time} WIB via '{device_id}'.")
                return {
                    "device_id": device_id,
                    "nama": lcd_name,
                    "status": "HADIR PULANG",
                    "pesan": "HADIR PULANG",
                    "kode": "SUCCESS",
                    "sesi": sesi_name
                }
        except Exception as ex_guru:
            logging.error(f"[ERROR GURU PRESENSI] Gagal memproses presensi guru '{entity_name}': {ex_guru}")
            cursor.close()
            return {
                "device_id": device_id,
                "nama": lcd_name,
                "status": "GAGAL SIMPAN",
                "pesan": "SYSTEM ERROR",
                "kode": "ERROR",
                "sesi": "Presensi Guru"
            }

    # 3. Penentuan Sesi Dinamis (menggunakan cache cepat WIB)
    sesi = get_active_sesi(cursor, device_id, current_time, strict=True, force_refresh=False)
    if not sesi:
        # Coba sekali lagi dengan force_refresh jika barusan saja ada sesi baru dibuat
        sesi = get_active_sesi(cursor, device_id, current_time, strict=True, force_refresh=True)

    if not sesi:
        cursor.close()
        logging.warning(f"[NO ACTIVE SESSION] Tap dari '{entity_name}' ditolak (jam WIB {current_time}, device='{device_id}').")
        return {
            "device_id": device_id,
            "nama": lcd_name,
            "status": "TIDAK ADA SESI",
            "kode": "NO_SESSION"
        }

    sesi_id = sesi["id"]
    sesi_name = sesi["nama_sesi"]

    # 4. Validasi Filter Target Sesi
    target_partisipan = str(
        sesi.get("alloc_target_kategori")
        or sesi.get("target_partisipan")
        or sesi.get("target_kategori")
        or ""
    ).strip().lower()

    filter_target = str(
        sesi.get("alloc_target_filter")
        or sesi.get("filter_target")
        or sesi.get("target_filter")
        or ""
    ).strip()

    target_gender = str(sesi.get("alloc_target_gender") or "").strip().upper()
    if not target_gender or target_gender == "ALL":
        if target_partisipan in ["gender_putra"] or "[gender: putra]" in filter_target.lower():
            target_gender = "L"
        elif target_partisipan in ["gender_putri"] or "[gender: putri]" in filter_target.lower():
            target_gender = "P"

    has_gender_requirement = target_gender in ["L", "P"]
    is_kamar_session = target_partisipan in ["kamar", "kamar_khusus", "kamar khusus"]
    has_target_filter = bool(filter_target) and filter_target.lower() not in ["", "semua_santri", "semua santri", "all", "semua", "-"]

    if has_gender_requirement or is_kamar_session or has_target_filter or target_partisipan in ["kelas_pengajian", "pengajian", "gender", "kelas_sekolah", "sekolah"]:
        if entity_type == "santri":
            santri_kamar = entity.get("kamar") or ""
            santri_raw_gender = entity.get("jenis_kelamin") or ""
            santri_norm_gender = normalize_gender(santri_raw_gender)
            santri_kelas_pengajian = (entity.get("kelas_pengajian") or "").strip().lower()

            if target_gender in ["L", "P"] and santri_norm_gender != target_gender:
                cursor.close()
                logging.warning(f"[REJECT GENDER] Santri {entity_name} ditolak di mesin {device_id} (target: {target_gender})")
                return {
                    "device_id": device_id,
                    "nama": lcd_name,
                    "status": "AKSES DITOLAK",
                    "pesan": "BUKAN PERUNTUKAN",
                    "kode": "WRONG_GENDER",
                    "sesi": sesi_name
                }

            if target_partisipan in ["kelas_pengajian", "pengajian"]:
                matched = True
                target_lower = filter_target.lower()
                if "[gender: putra]" in target_lower and santri_norm_gender != "L":
                    matched = False
                elif "[gender: putri]" in target_lower and santri_norm_gender != "P":
                    matched = False

                classes_part = re.sub(r"\[gender:\s*(putra|putri)\]", "", filter_target, flags=re.IGNORECASE).strip()
                target_classes = [c.strip().lower() for c in classes_part.split(",") if c.strip()]
                if matched and target_classes:
                    if not santri_kelas_pengajian or not any(tc in santri_kelas_pengajian or santri_kelas_pengajian in tc for tc in target_classes):
                        matched = False

                if not matched:
                    cursor.close()
                    return {
                        "device_id": device_id,
                        "nama": lcd_name,
                        "status": "AKSES DITOLAK",
                        "pesan": "BUKAN KELAS INI",
                        "kode": "WRONG_CLASS",
                        "sesi": sesi_name
                    }

            elif is_kamar_session or (target_partisipan in ["kamar", "kamar_khusus"] and has_target_filter):
                if not is_target_room_matched(santri_kamar, filter_target):
                    cursor.close()
                    return {
                        "device_id": device_id,
                        "nama": lcd_name,
                        "status": "AKSES DITOLAK",
                        "pesan": "BUKAN KAMAR INI",
                        "kode": "WRONG_ROOM",
                        "sesi": sesi_name
                    }

            elif target_partisipan in ["sekolah", "kelas_sekolah", "jenjang"] and has_target_filter:
                santri_jenjang = (entity.get("jenjang") or "").strip().lower()
                target_levels = [lvl.strip().lower() for lvl in filter_target.split(",") if lvl.strip()]
                matched_level = (filter_target.lower() in ["all", "semua", ""]) or any(
                    lvl == santri_jenjang or lvl in santri_jenjang or santri_jenjang in lvl
                    for lvl in target_levels
                )
                if target_levels and not matched_level:
                    cursor.close()
                    return {
                        "device_id": device_id,
                        "nama": lcd_name,
                        "status": "AKSES DITOLAK",
                        "pesan": "BUKAN JENJANG INI",
                        "kode": "WRONG_LEVEL",
                        "sesi": sesi_name
                    }

    # 5. Anti-Duplikasi Per Sesi (menggunakan range waktu_tap agar memakai indeks!)
    day_start = f"{today_date} 00:00:00"
    day_end = f"{today_date} 23:59:59"
    cursor.execute(
        """
        SELECT id FROM presensis
        WHERE sesi_id = %s AND tipe_entitas = %s AND entitas_id = %s
          AND waktu_tap BETWEEN %s AND %s
        LIMIT 1
        """,
        (sesi_id, entity_type, entity_id, day_start, day_end)
    )
    if cursor.fetchone():
        cursor.close()
        logging.info(f"[DUPLICATE] '{entity_name}' sudah absen pada sesi '{sesi_name}' hari ini.")
        return {
            "device_id": device_id,
            "nama": lcd_name,
            "status": "SUDAH ABSEN",
            "kode": "DUPLICATE",
            "sesi": sesi_name
        }

    # 6. Tentukan Status Kehadiran (Tepat Waktu vs Terlambat)
    status_kehadiran = "tepat_waktu"
    if sesi and sesi.get("toleransi_menit"):
        try:
            jm_str = format_time_str(sesi["jam_mulai"])
            jam_mulai_dt = datetime.strptime(f"{today_date} {jm_str}", "%Y-%m-%d %H:%M:%S")
            toleransi_dt = jam_mulai_dt + timedelta(minutes=int(sesi["toleransi_menit"]))
            if now > toleransi_dt:
                status_kehadiran = "terlambat"
        except Exception:
            pass

    if sesi and sesi.get("kategori") == "makan":
        status_kehadiran = "sudah_makan"

    # 7. Simpan Log Presensi
    cursor.execute(
        """
        INSERT INTO presensis (device_id, sesi_id, tipe_entitas, entitas_id, rfid_uid, waktu_tap, status_kehadiran, created_at, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
        """,
        (device_id, sesi_id, entity_type, entity_id, clean_uid, now_full, status_kehadiran)
    )

    if entity_type == "santri":
        try:
            cursor.execute(
                """
                INSERT INTO presensi_santris (siswa_id, rfid_uid, device_id, tanggal, waktu, status, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, 'Hadir', NOW(), NOW())
                """,
                (entity_id, clean_uid, device_id, today_date, current_time)
            )
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


def publish_tap_response(client, device_id: str, compact_resp: dict):
    """Kirim respons tap kartu secara instan (QoS 0) ke topik perangkat terkait."""
    response_json = json.dumps(compact_resp, separators=(",", ":"))
    published_topics = set()
    for dev_var in get_device_publish_variants(device_id):
        t1 = f"presensi/devices/{dev_var}/response"
        t2 = f"presensi/santri/response/{dev_var}"
        if t1 not in published_topics:
            client.publish(t1, response_json, qos=0)
            published_topics.add(t1)
        if t2 not in published_topics:
            client.publish(t2, response_json, qos=0)
            published_topics.add(t2)
    client.publish(MQTT_TOPIC_RESPONSE, response_json, qos=0)
    logging.info(f"[RESP -> {device_id}] {response_json}")


# ================= BACKGROUND PERIODIC RUNNER =================
def periodic_session_broadcaster(client):
    """
    Thread mandiri dengan KONEKSI MYSQL DEDIKATED (tidak pernah berbagi koneksi dengan on_message).
    Memeriksa perubahan file sinyal /tmp/rfid_sesi_dirty setiap 1 detik dan polling jadwal tiap 3 detik.
    """
    logging.info(f"[BROADCASTER] Thread broadcast sesi aktif dimulai (WIB sekarang: {now_wib().strftime('%Y-%m-%d %H:%M:%S')}).")
    broadcaster_db = None
    last_dirty_seen = get_dirty_signal_mtime()
    last_check_ts = 0.0

    while True:
        try:
            dirty_mtime = get_dirty_signal_mtime()
            dirty_changed = dirty_mtime > last_dirty_seen
            now_ts = time.time()

            if dirty_changed or (now_ts - last_check_ts) >= BROADCAST_CHECK_INTERVAL:
                broadcaster_db = ensure_db_connection(broadcaster_db, label="broadcaster")
                if broadcaster_db:
                    broadcast_all_devices(client, broadcaster_db, force_publish=dirty_changed)
                    last_check_ts = now_ts
                    if dirty_changed:
                        last_dirty_seen = dirty_mtime
                        logging.info("[BROADCASTER] Perubahan sesi dari Web terdeteksi, broadcast ulang instan selesai.")
        except Exception as err:
            logging.warning(f"[BROADCASTER ERROR] {err}")
            try:
                if broadcaster_db:
                    broadcaster_db.close()
            except Exception:
                pass
            broadcaster_db = None
        time.sleep(1.0)


# ================= MQTT CALLBACKS =================
def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        logging.info(f"Terhubung ke Broker MQTT ({MQTT_BROKER}:{MQTT_PORT})! Waktu WIB: {now_wib().strftime('%H:%M:%S')}")
        client.subscribe(MQTT_TOPIC_TAP, qos=0)
        client.subscribe("presensi/devices/+/tap", qos=0)
        client.subscribe(MQTT_TOPIC_HEARTBEAT, qos=0)
        client.subscribe("presensi/devices/+/heartbeat", qos=0)
        client.subscribe("presensi/devices/+/ping", qos=0)
        client.subscribe("presensi/get_sesi", qos=0)
        client.subscribe("presensi/devices/+/get_sesi", qos=0)

        global _cleared_global_retained
        _cleared_global_retained = False
        db = ensure_db_connection(userdata.get("db") if userdata else None, label="handler")
        if userdata is not None:
            userdata["db"] = db
        if db:
            try:
                broadcast_all_devices(client, db, force_publish=True)
            except Exception as e:
                logging.warning(f"Initial broadcast error: {e}")
    else:
        logging.error(f"Koneksi MQTT Gagal dengan kode: {rc}")


def on_message(client, userdata, msg):
    t_start = time.time()
    try:
        payload_raw = msg.payload.decode("utf-8", errors="ignore").strip()
        try:
            data = json.loads(payload_raw)
        except json.JSONDecodeError:
            logging.warning(f"Payload bukan JSON valid di [{msg.topic}]: {payload_raw}")
            return

        raw_device_id = data.get("device_id") or data.get("serial") or data.get("chip_id") or "tap_masjid"
        device_id = str(raw_device_id).strip()
        ip_addr = str(data.get("ip", "")).strip()
        tap_seq = data.get("tap_seq")
        register_device_alias(device_id)

        db = ensure_db_connection(userdata.get("db") if userdata else None, label="handler")
        if userdata is not None:
            userdata["db"] = db

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
                sync_device_presence(cur, device_id, ip_addr, force_db_update=True)
                cur.close()
                broadcast_device_session(client, db, device_id, force_refresh=True)
            return

        candidates = [
            data.get("rfid_uid"),
            data.get("uid"),
            data.get("rfid_uid_pad"),
            data.get("rfid_uid_rev"),
            data.get("rfid_uid_rev_pad"),
            data.get("uid_rev"),
        ]
        candidates = [str(c).strip() for c in candidates if c is not None and str(c).strip()]
        if not candidates:
            logging.warning("Pesan tap diterima tanpa parameter 'rfid_uid' / 'uid'. Diabaikan.")
            return

        clean_uid = candidates[0].upper()
        norm_dev = normalize_device_id(device_id)
        debounce_key = (norm_dev, clean_uid)
        now_ts = time.time()

        # Debounce cerdas: balas ulang respons cache agar ESP32 TIDAK PERNAH menunggu timeout 3 detik!
        with _state_lock:
            prev_tap = tap_history.get(debounce_key)
        if prev_tap and (now_ts - prev_tap[0]) < DEBOUNCE_SECONDS:
            cached_resp = dict(prev_tap[1])
            cached_resp["device_id"] = device_id
            if tap_seq is not None:
                cached_resp["tap_seq"] = tap_seq
            publish_tap_response(client, device_id, cached_resp)
            logging.info(f"[DEBOUNCE-FAST] UID '{clean_uid}' di '{device_id}' membalas cache ({(now_ts - prev_tap[0]):.2f}s).")
            return

        if not db:
            logging.error("Database offline! Tidak dapat memproses tap.")
            response_payload = {
                "device_id": device_id,
                "nama": "Server Offline",
                "status": "AKSES DITOLAK",
                "kode": "SERVER_OFFLINE"
            }
        else:
            response_payload = process_tap(db, clean_uid, device_id, ip_addr, all_candidates=candidates)

        compact_resp = {
            "device_id": str(response_payload.get("device_id", device_id)),
            "nama": str(response_payload.get("nama", "Santri"))[:16],
            "status": str(response_payload.get("status", "BERHASIL ABSEN"))[:16],
            "kode": str(response_payload.get("kode", "OK")),
        }
        if tap_seq is not None:
            compact_resp["tap_seq"] = tap_seq

        with _state_lock:
            tap_history[debounce_key] = (now_ts, dict(compact_resp))
            # Bersihkan riwayat lama secara berkala agar RAM tetap rendah
            if len(tap_history) > 500:
                cutoff = now_ts - max(10.0, DEBOUNCE_SECONDS * 2)
                for k in [k for k, v in tap_history.items() if v[0] < cutoff]:
                    tap_history.pop(k, None)

        publish_tap_response(client, device_id, compact_resp)
        elapsed_ms = (time.time() - t_start) * 1000.0
        logging.info(f"[PERF] Tap '{clean_uid}' selesai dalam {elapsed_ms:.1f} ms")

    except Exception as ex:
        logging.error(f"Error memproses pesan MQTT: {ex}", exc_info=True)


# ================= RUNNER =================
def main():
    logging.info("=" * 65)
    logging.info("Memulai Multi-Location Presensi Daemon v3.0 (WIB-Locked & Fast Tap)")
    logging.info(f"Waktu WIB : {now_wib().strftime('%Y-%m-%d %H:%M:%S')}")
    logging.info(f"Broker    : {MQTT_BROKER}:{MQTT_PORT}")
    logging.info("=" * 65)

    db = None
    retry_count = 0
    while not db:
        db = create_db_connection(label="handler")
        if not db:
            retry_count += 1
            logging.warning(f"Menunggu koneksi DB... percobaan #{retry_count} dalam 3 detik.")
            time.sleep(3)

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

        broadcaster_thread = threading.Thread(
            target=periodic_session_broadcaster,
            args=(client,),
            daemon=True
        )
        broadcaster_thread.start()

        client.loop_forever()
    except KeyboardInterrupt:
        logging.info("Daemon dihentikan oleh pengguna.")
    finally:
        if userdata.get("db"):
            try:
                userdata["db"].close()
            except Exception:
                pass
        client.disconnect()


if __name__ == "__main__":
    main()
