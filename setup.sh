#!/usr/bin/env bash
# =====================================================================
# Setup sekali jalan (macOS / Linux): database, pipeline ETL, dan website.
#   Prasyarat: XAMPP dengan MySQL/MariaDB menyala, Python 3.10+, PHP 8.2+, Composer, Node.js 20+.
#   Jalankan dari folder repo:  ./setup.sh
#   Setelah selesai:            cd web && php artisan serve   -> http://localhost:8000
# Menjalankan ulang aman: database dibuat ulang dari file mentah di data/raw.
# =====================================================================
set -euo pipefail
cd "$(dirname "$0")"

langkah() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
gagal()   { printf '\n\033[1;31mGagal:\033[0m %s\n' "$*" >&2; exit 1; }

# --- cek prasyarat
for p in python3 php composer npm; do
    command -v "$p" >/dev/null || gagal "'$p' belum terpasang (lihat Prasyarat di README)."
done
MYSQL="${MYSQL:-}"
# klien mysql milik XAMPP didahulukan: klien MySQL 9 (mis. dari Homebrew) tidak bisa login ke MariaDB XAMPP
for kandidat in /Applications/XAMPP/xamppfiles/bin/mysql /opt/lampp/bin/mysql "$(command -v mariadb || true)" "$(command -v mysql || true)"; do
    [ -z "$MYSQL" ] && [ -n "$kandidat" ] && [ -x "$kandidat" ] && MYSQL="$kandidat"
done
[ -n "$MYSQL" ] || gagal "Program mysql tidak ditemukan. Pasang XAMPP atau isi variabel MYSQL=/path/ke/mysql."

# --- file konfigurasi
langkah "Menyiapkan file .env"
[ -f .env ] || cp .env.example .env
[ -f web/.env ] || cp web/.env.example web/.env
PASS="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- || true)"
MYSQL_PWD="$PASS" "$MYSQL" -h 127.0.0.1 -u root -e "SELECT 1" >/dev/null 2>&1 \
    || gagal "Tidak bisa terhubung ke MariaDB di 127.0.0.1:3306. Nyalakan MySQL di XAMPP dulu."

langkah "Membuat 3 database (staging, master, dashboard)"
MYSQL_PWD="$PASS" "$MYSQL" -h 127.0.0.1 -u root < sql/schema.sql

langkah "Memasang library Python (virtual env .venv)"
python3 -m venv .venv
.venv/bin/pip install -q --disable-pip-version-check -r etl/requirements.txt

langkah "Menjalankan pipeline ETL (extract -> transform/load -> replikasi)"
(cd etl && ../.venv/bin/python run_etl.py)

langkah "Menyiapkan website Laravel"
cd web
composer install --no-interaction --quiet
npm install --silent
npm run build --silent
grep -qE '^APP_KEY=.+' .env || php artisan key:generate --force
php artisan migrate --seed --force

printf '\n\033[1;32mSelesai.\033[0m Jalankan:\n\n    cd web && php artisan serve\n\nlalu buka http://localhost:8000 (akun demo ada di halaman login).\n'
