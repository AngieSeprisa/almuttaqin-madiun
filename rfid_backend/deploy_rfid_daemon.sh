#!/usr/bin/env bash
set -u

REPO_DIR="/var/www/al-muttaqin"
SRC_PY="${REPO_DIR}/rfid_backend/presensi_daemon.py"
SRC_SVC="${REPO_DIR}/rfid_backend/rfid-presensi.service"

if [ ! -f "$SRC_PY" ]; then
    echo "[RFID-DEPLOY] File $SRC_PY tidak ditemukan, lewati."
    exit 0
fi

SUDO=""
if [ "$(id -u)" -ne 0 ]; then
    SUDO="sudo"
fi

safe_copy_py() {
    local src="$1"
    local dst="$2"
    if [ -z "$dst" ]; then
        return 0
    fi
    $SUDO mkdir -p "$(dirname "$dst")"
    local real_src real_dst
    real_src=$(readlink -f "$src" 2>/dev/null || echo "$src")
    real_dst=$(readlink -f "$dst" 2>/dev/null || echo "$dst")
    if [ "$real_src" != "$real_dst" ]; then
        $SUDO cp -f "$src" "$dst"
        echo "[RFID-DEPLOY] Salin presensi_daemon.py -> $dst"
    else
        echo "[RFID-DEPLOY] Target $dst sudah identik dengan sumber."
    fi
    $SUDO chmod +x "$dst" 2>/dev/null || true
}

# 1. Selalu pastikan /opt/rfid-backend/presensi_daemon.py mutakhir (tanpa menimpa .env)
safe_copy_py "$SRC_PY" "/opt/rfid-backend/presensi_daemon.py"

# 2. Cari unit systemd non-symlink di /etc/systemd/system yang menjalankan presensi_daemon.py
FOUND_UNIT=""
for unit_file in /etc/systemd/system/*.service; do
    [ -f "$unit_file" ] || continue
    if grep -q "presensi_daemon.py" "$unit_file" 2>/dev/null; then
        FOUND_UNIT="$unit_file"
        break
    fi
done

RFID_SVC="rfid-presensi"

if [ -n "$FOUND_UNIT" ]; then
    RFID_SVC=$(basename "$FOUND_UNIT" .service)
    echo "[RFID-DEPLOY] Menemukan unit systemd: $FOUND_UNIT (service: $RFID_SVC)"

    # Ekstrak path presensi_daemon.py dari ExecStart dan pastikan TZ=Asia/Jakarta ada
    UNIT_EXEC_PY=$(python3 - "$FOUND_UNIT" <<'PYEOF'
import re, sys
path = sys.argv[1]
text = open(path, "r", encoding="utf-8", errors="ignore").read()
m = re.search(r'(/[^\s\'"]+presensi_daemon\.py)', text)
print(m.group(1) if m else "")
PYEOF
)
    if [ -n "$UNIT_EXEC_PY" ]; then
        safe_copy_py "$SRC_PY" "$UNIT_EXEC_PY"
    fi

    if ! grep -q 'TZ=Asia/Jakarta' "$FOUND_UNIT" 2>/dev/null; then
        $SUDO python3 - "$FOUND_UNIT" <<'PYEOF'
import sys
path = sys.argv[1]
lines = open(path, "r", encoding="utf-8", errors="ignore").read().splitlines(True)
out = []
inserted = False
for line in lines:
    out.append(line)
    if not inserted and line.strip() == "[Service]":
        out.append('Environment="TZ=Asia/Jakarta"\n')
        inserted = True
if inserted:
    open(path, "w", encoding="utf-8").writelines(out)
    print(f"[RFID-DEPLOY] Menambahkan Environment=TZ=Asia/Jakarta ke {path}")
PYEOF
    fi
else
    echo "[RFID-DEPLOY] Unit systemd belum ada, memasang /etc/systemd/system/rfid-presensi.service"
    $SUDO cp -f "$SRC_SVC" /etc/systemd/system/rfid-presensi.service
fi

# 3. Sinkronkan juga jika ada proses presensi_daemon.py dari path lain
RUNNING_PATHS=$(ps -eo args 2>/dev/null | grep -E 'presensi_daemon\.py' | grep -v grep | grep -oE '/[^[:space:]]+presensi_daemon\.py' | sort -u || true)
if [ -n "$RUNNING_PATHS" ]; then
    while IFS= read -r rpath; do
        [ -n "$rpath" ] && safe_copy_py "$SRC_PY" "$rpath"
    done <<< "$RUNNING_PATHS"
fi

# 4. Reload systemd dan restart daemon RFID
$SUDO systemctl daemon-reload
$SUDO systemctl enable "$RFID_SVC" || true
$SUDO systemctl restart "$RFID_SVC"
sleep 2

echo "[RFID-DEPLOY] Status service $RFID_SVC:"
$SUDO systemctl status "$RFID_SVC" --no-pager -n 15 || true
echo "[RFID-DEPLOY] Log terbaru $RFID_SVC:"
$SUDO journalctl -u "$RFID_SVC" -n 20 --no-pager || true
