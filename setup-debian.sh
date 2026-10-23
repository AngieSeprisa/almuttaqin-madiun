#!/usr/bin/env bash
# ==============================================================================
# Script Otomasi Penyiapan Server Debian untuk Laravel
# Proyek: Pondok Pesantren Al Muttaqin
# ==============================================================================

set -euo pipefail

# Warna output terminal
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}=== Memulai Penyiapan Server Debian untuk Laravel ===${NC}\n"

# ------------------------------------------------------------------------------
# 1. Pasang MariaDB Server jika belum ada (gunakan sudo)
# ------------------------------------------------------------------------------
echo -e "${YELLOW}[1/5] Memeriksa dan memasang MariaDB Server...${NC}"
if ! command -v mariadb &> /dev/null && ! command -v mysql &> /dev/null; then
    echo "MariaDB belum terpasang. Memperbarui repositori & memasang mariadb-server..."
    sudo apt update -y
    sudo apt install -y mariadb-server
else
    echo "MariaDB Server sudah terpasang."
fi

sudo systemctl enable mariadb
sudo systemctl start mariadb
echo -e "${GREEN}âœ“ MariaDB Server siap dan berjalan.${NC}\n"

# ------------------------------------------------------------------------------
# 2. Buat database baru bernama 'db_almuttaqin' dengan user 'almuttaqin-madiun'
#    dan password '@almuttaqin354'. Berikan seluruh privileges.
# ------------------------------------------------------------------------------
echo -e "${YELLOW}[2/5] Mengonfigurasi Database dan User MariaDB...${NC}"
DB_NAME="db_almuttaqin"
DB_USER="almuttaqin-madiun"
DB_PASS="@almuttaqin354"

sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
sudo mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
echo -e "${GREEN}âœ“ Database '${DB_NAME}' dan user '${DB_USER}' berhasil dibuat dengan hak akses penuh.${NC}\n"

# ------------------------------------------------------------------------------
# 3. Buat direktori /var/www/almuttaqin-madiun dan atur ownership-nya ke user
#    'almuttaqin-madiun:almuttaqin-madiun'.
# ------------------------------------------------------------------------------
echo -e "${YELLOW}[3/5] Menyiapkan direktori /var/www/almuttaqin-madiun...${NC}"
# Buat user Linux 'almuttaqin-madiun' jika belum ada agar chown berhasil
if ! id -u almuttaqin-madiun >/dev/null 2>&1; then
    echo "User Linux 'almuttaqin-madiun' belum ditemukan di sistem, membuat user..."
    sudo useradd -m -s /bin/bash almuttaqin-madiun
fi

sudo mkdir -p /var/www/almuttaqin-madiun
sudo chown -R almuttaqin-madiun:almuttaqin-madiun /var/www/almuttaqin-madiun
sudo chmod -R 775 /var/www/almuttaqin-madiun
echo -e "${GREEN}âœ“ Direktori /var/www/almuttaqin-madiun berhasil dibuat dan ownership diatur ke almuttaqin-madiun:almuttaqin-madiun.${NC}\n"

# ------------------------------------------------------------------------------
# 4. Buat file konfigurasi virtual host Nginx di /etc/nginx/sites-available/laravel
#    yang mengarah ke /var/www/proyek-sekolah/public dengan dukungan PHP-FPM,
#    lalu buat symlink ke sites-enabled dan hapus file default Nginx.
# ------------------------------------------------------------------------------
echo -e "${YELLOW}[4/5] Mengonfigurasi Virtual Host Nginx...${NC}"

# Pastikan Nginx terpasang
if ! command -v nginx &> /dev/null; then
    echo "Nginx belum terpasang. Memasang Nginx..."
    sudo apt update -y
    sudo apt install -y nginx
fi

# Deteksi socket PHP-FPM yang aktif di sistem (misal php8.2-fpm.sock)
PHP_SOCK=$(find /run/php/ -name "php*-fpm.sock" 2>/dev/null | sort -V | tail -n 1)
if [ -z "$PHP_SOCK" ]; then
    PHP_SOCK="/run/php/php8.2-fpm.sock"
fi
echo "Menggunakan PHP-FPM Socket: $PHP_SOCK"

# Buat direktori target root jika belum ada agar nginx -t lolos validasi
sudo mkdir -p /var/www/proyek-sekolah/public

# Tulis konfigurasi virtual host Nginx
sudo tee /etc/nginx/sites-available/laravel > /dev/null <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name _;

    root /var/www/proyek-sekolah/public;
    index index.php index.html index.htm;

    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:${PHP_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Buat symlink ke sites-enabled
sudo ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel

# Hapus default Nginx di sites-enabled
if [ -f /etc/nginx/sites-enabled/default ]; then
    sudo rm -f /etc/nginx/sites-enabled/default
    echo "File default Nginx di sites-enabled berhasil dihapus."
fi
echo -e "${GREEN}âœ“ Konfigurasi Nginx di /etc/nginx/sites-available/laravel berhasil dibuat dan diaktifkan.${NC}\n"

# ------------------------------------------------------------------------------
# 5. Jalankan 'nginx -t' dan restart service Nginx.
# ------------------------------------------------------------------------------
echo -e "${YELLOW}[5/5] Menguji sintaks Nginx dan melakukan restart service...${NC}"
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl enable nginx
echo -e "${GREEN}âœ“ Service Nginx berhasil direstart dan berjalan normal.${NC}\n"

echo -e "${GREEN}================================================================${NC}"
echo -e "${GREEN} SEMUA TAHAP PENYIAPAN SERVER DEBIAN TELAH SELESAI DENGAN SUKSES! ${NC}"
echo -e "${GREEN}================================================================${NC}"
