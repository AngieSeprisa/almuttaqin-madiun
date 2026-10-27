# Panduan Backend Presensi RFID Santri (ESP32 <-> MQTT <-> Python <-> MySQL)
**Pondok Pesantren Yayasan Muttaqin Kota Madiun**

Folder ini berisi skrip backend Python lengkap untuk memproses tap kartu RFID dari perangkat ESP32, mencocokkan identitas santri Kamar Reguler, mencatat log kehadiran, dan mengirim status kembali ke LCD alat secara realtime.

---

## 📂 Struktur File
- **`presensi_daemon.py`** : Skrip utama Python daemon yang terhubung ke MQTT dan MySQL.
- **`setup_database.sql`** : Skrip SQL untuk menambah kolom `rfid_uid` pada santri, membuat tabel `presensi_santris`, dan inisialisasi data 16 santri.
- **`requirements.txt`** : Daftar paket pustaka Python (`paho-mqtt`, `mysql-connector-python`, `python-dotenv`).
- **`rfid-presensi.service`** : Konfigurasi service systemd agar skrip berjalan 24/7 di latar belakang VPS dan auto-restart saat reboot.
- **`.env.example`** : Template kredensial database dan MQTT.

---

## 🚀 Langkah Instalasi Cepat di VPS (202.155.17.96)

### 1. Eksekusi Skema Database
Masuk ke terminal VPS atau phpMyAdmin, lalu jalankan file `setup_database.sql` pada database `db_muttaqin`:
```bash
mysql -u root -p db_muttaqin < /path/ke/setup_database.sql
```
*(Atau jika menggunakan Laravel artisan migration):*
```bash
php artisan migrate
```

### 2. Salin Folder ke VPS
Letakkan folder ini di VPS, misalnya di `/opt/rfid-backend`:
```bash
sudo mkdir -p /opt/rfid-backend
# Salin seluruh isi folder rfid_backend ke direktori di atas
```

### 3. Pasang Dependensi Python di VPS
```bash
sudo apt update
sudo apt install -y python3-pip python3-venv

pip3 install -r /opt/rfid-backend/requirements.txt
```

### 4. Konfigurasi File `.env`
Salin template konfigurasi dan sesuaikan jika ada password database:
```bash
cp /opt/rfid-backend/.env.example /opt/rfid-backend/.env
nano /opt/rfid-backend/.env
```
Pastikan `DB_NAME=db_muttaqin` dan `DB_PASSWORD` sesuai dengan setelan VPS Anda.

### 5. Pasang dan Aktifkan Systemd Service
```bash
# Salin file unit service ke systemd
sudo cp /opt/rfid-backend/rfid-presensi.service /etc/systemd/system/

# Muat ulang konfigurasi daemon systemd
sudo systemctl daemon-reload

# Aktifkan agar otomatis berjalan saat VPS reboot
sudo systemctl enable rfid-presensi

# Jalankan service sekarang
sudo systemctl start rfid-presensi
```

### 6. Cek Status dan Log Realtime
Untuk memeriksa apakah service sudah aktif dan terhubung ke MQTT & MySQL:
```bash
sudo systemctl status rfid-presensi
```

Untuk memantau log tap kartu secara live saat santri men-tap kartu di ESP32:
```bash
journalctl -u rfid-presensi -f
```

---

## 📡 Rincian Topik & Payload MQTT
1. **ESP32 Publish ke Topik:** `presensi/santri/tap`
   ```json
   {
     "device_id": "ESP32_Presensi_Santri",
     "uid": "C1F8EFA7"
   }
   ```

2. **Backend Membalas ke Topik:** `presensi/santri/response`
   - **Jika Kartu Terdaftar:**
     ```json
     {
       "nama": "Moch Rokib",
       "status": "Hadir / Terdaftar"
     }
     ```
   - **Jika Sudah Tap Hari Ini:**
     ```json
     {
       "nama": "Moch Rokib",
       "status": "Sudah Hadir"
     }
     ```
   - **Jika Kartu Belum Terdaftar:**
     ```json
     {
       "nama": "Tak Terdaftar",
       "status": "Hubungi Pamong"
     }
     ```
