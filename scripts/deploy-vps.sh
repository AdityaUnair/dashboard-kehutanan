#!/usr/bin/env bash
# =====================================================================
# Deploy ke VPS (Ubuntu 24.04, mis. Oracle Cloud Always Free) dalam satu perintah.
# Hasilnya sama dengan di laptop: Laravel + MariaDB master (3306) & slave (3307) + ETL Python,
# tetapi menyala 24 jam dan ikut menyala lagi sendiri setelah server reboot.
#
#   Pertama kali (di server, sebagai user biasa yang punya sudo, mis. "ubuntu"):
#     sudo apt-get install -y git
#     git clone https://github.com/AdityaUnair/dashboard-kehutanan.git ~/dashboard-kehutanan
#     bash ~/dashboard-kehutanan/scripts/deploy-vps.sh
#   Memperbarui setelah ada commit baru di GitHub:
#     cd ~/dashboard-kehutanan && git pull && bash scripts/deploy-vps.sh
#     (database dibangun ulang dari file mentah di data/raw; akun login tetap)
#
# Yang dipasang: nginx, PHP-FPM, MariaDB, Python venv, Composer, Node.js 22 (untuk build tampilan).
# Website dibuka di http://ALAMAT-IP-SERVER (port 80). Port database TIDAK dibuka ke internet.
# =====================================================================
set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
PENGGUNA="$(id -un)"
langkah() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
gagal()   { printf '\n\033[1;31mGagal:\033[0m %s\n' "$*" >&2; exit 1; }

[ "$(id -u)" -ne 0 ] || gagal "Jalankan sebagai user biasa yang punya sudo (mis. ubuntu), bukan root."
command -v apt-get >/dev/null || gagal "Script ini untuk Ubuntu/Debian."
sudo -v

# --- 1. paket sistem
langkah "Memasang paket sistem (nginx, PHP, MariaDB, Python, Composer)"
sudo apt-get update -qq
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    nginx mariadb-server mariadb-client git unzip curl perl \
    php-fpm php-cli php-mysql php-mbstring php-xml php-curl php-zip php-bcmath php-intl \
    composer python3 python3-venv >/dev/null
php -r 'exit(version_compare(PHP_VERSION, "8.3", ">=") ? 0 : 1);' \
    || gagal "Butuh PHP 8.3+ (Laravel 13). Pakai image Ubuntu 24.04."

if ! command -v node >/dev/null || [ "$(node -p 'process.versions.node.split(".")[0]')" -lt 20 ]; then
    langkah "Memasang Node.js 22 (repo resmi NodeSource) untuk build tampilan"
    curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - >/dev/null
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq nodejs >/dev/null
fi

# --- 2. MariaDB master: binary log aktif untuk replikasi, hanya mendengar 127.0.0.1
langkah "Menyiapkan MariaDB master (port 3306, binary log aktif)"
CNF_MASTER=/etc/mysql/mariadb.conf.d/60-kehutanan.cnf
if [ ! -f "$CNF_MASTER" ]; then
    sudo tee "$CNF_MASTER" >/dev/null <<'CNF'
# Dashboard Kehutanan: master untuk replikasi Master-Slave (slave = layanan kehutanan-replika, port 3307)
[mysqld]
bind-address=127.0.0.1
server-id=1
log_bin=mysql-bin
binlog_format=ROW
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
CNF
    sudo systemctl restart mariadb
fi
sudo systemctl enable --quiet mariadb

# --- 3. file .env: password root MariaDB dibuat acak sekali, disimpan di .env (tidak ikut ke GitHub)
langkah "Menyiapkan file .env"
cd "$APP_DIR"
[ -f .env ] || cp .env.example .env
[ -f web/.env ] || cp web/.env.example web/.env
PASS="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- || true)"
if [ -z "$PASS" ]; then
    PASS="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 24)"
    perl -pi -e "s/^DB_PASSWORD=.*/DB_PASSWORD=$PASS/" .env
fi
# root bisa masuk lewat sudo (unix_socket) maupun lewat TCP 127.0.0.1 dengan password
sudo mariadb -e "
    ALTER USER 'root'@'localhost' IDENTIFIED VIA unix_socket OR mysql_native_password USING PASSWORD('$PASS');
    CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '$PASS';
    ALTER USER 'root'@'127.0.0.1' IDENTIFIED BY '$PASS';
    GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
    FLUSH PRIVILEGES;"

atur_web() {  # atur_web NAMA NILAI -> tulis/ganti baris di web/.env
    if grep -q "^$1=" web/.env; then perl -pi -e "s|^$1=.*|$1=$2|" web/.env; else printf '%s=%s\n' "$1" "$2" >> web/.env; fi
}
IP="$(curl -fsS --max-time 5 https://ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')"
atur_web APP_ENV production
atur_web APP_DEBUG false
atur_web APP_URL "http://$IP"
atur_web DB_USERNAME root
atur_web DB_PASSWORD "$PASS"

# --- 4. database + ETL + Laravel: langkah yang sama dengan setup di laptop
# Saat deploy ulang, slave dihentikan dulu: database master dibangun ulang, lalu slave disalin ulang di langkah 5
sudo systemctl stop kehutanan-replika 2>/dev/null || true
langkah "Menjalankan setup.sh (database, ETL, website)"
MYSQL="$(command -v mariadb)" ./setup.sh

# --- 5. slave (port 3307) sebagai layanan systemd agar menyala lagi setelah reboot
langkah "Menyiapkan replikasi Master-Slave (slave port 3307)"
REPLIKA_DIR="${REPLIKA_DIR:-$HOME/.kehutanan-replika}"
MYSQLD="$(command -v mariadbd || echo /usr/sbin/mariadbd)"
sudo tee /etc/systemd/system/kehutanan-replika.service >/dev/null <<UNIT
[Unit]
Description=Dashboard Kehutanan - MariaDB slave (port 3307)
After=network.target mariadb.service
Requires=mariadb.service

[Service]
User=$PENGGUNA
ExecStart=$MYSQLD --defaults-file=$REPLIKA_DIR/my.cnf
Restart=on-failure
LimitNOFILE=16384

[Install]
WantedBy=multi-user.target
UNIT
sudo systemctl daemon-reload
sudo systemctl enable --quiet kehutanan-replika
scripts/replika.sh setup

# --- 6. hak akses folder Laravel untuk nginx/PHP-FPM (user www-data)
langkah "Mengatur hak akses folder"
sudo chown -R "$PENGGUNA":www-data web/storage web/bootstrap/cache
sudo chmod -R g+rwX web/storage web/bootstrap/cache
sudo find web/storage web/bootstrap/cache -type d -exec chmod g+s {} +   # file baru ikut grup www-data
# .env berisi password database: root .env hanya untuk user ini, web/.env juga boleh dibaca PHP-FPM
chmod 600 .env
sudo chown "$PENGGUNA":www-data web/.env && sudo chmod 640 web/.env
# nginx perlu bisa menembus folder induk sampai web/public
d="$APP_DIR"; while [ "$d" != / ]; do sudo chmod o+x "$d"; d="$(dirname "$d")"; done
(cd web && php artisan config:clear >/dev/null && php artisan view:clear >/dev/null)

# --- 7. nginx
langkah "Menyiapkan nginx (port 80)"
SOCK="$(ls /run/php/php*-fpm.sock | head -n1)"
sudo tee /etc/nginx/sites-available/kehutanan >/dev/null <<NGINX
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    root $APP_DIR/web/public;
    index index.php;
    charset utf-8;
    client_max_body_size 8m;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$SOCK;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
NGINX
sudo ln -sf /etc/nginx/sites-available/kehutanan /etc/nginx/sites-enabled/kehutanan
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t -q
sudo systemctl enable --quiet nginx
sudo systemctl reload nginx || sudo systemctl restart nginx

# --- 8. firewall di dalam server: image Ubuntu Oracle Cloud memblokir semua port kecuali 22 lewat iptables
langkah "Membuka port 80 di firewall server"
if command -v ufw >/dev/null && sudo ufw status | grep -q "Status: active"; then
    sudo ufw allow 80/tcp >/dev/null
elif command -v iptables >/dev/null; then
    sudo iptables -C INPUT -p tcp --dport 80 -m conntrack --ctstate NEW -j ACCEPT 2>/dev/null \
        || sudo iptables -I INPUT 1 -p tcp --dport 80 -m conntrack --ctstate NEW -j ACCEPT
    command -v netfilter-persistent >/dev/null && sudo netfilter-persistent save >/dev/null 2>&1 || true
fi

printf '\n\033[1;32mSelesai.\033[0m Buka: http://%s\n' "$IP"
printf 'Kalau tidak bisa dibuka dari luar: pastikan port 80 (TCP) sudah dibuka di Security List / firewall penyedia VPS.\n'
