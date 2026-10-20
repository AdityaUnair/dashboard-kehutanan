#!/usr/bin/env bash
# =====================================================================
# Replikasi MASTER-SLAVE asli (opsional, untuk demo). macOS / Linux.
#
#   Master = MariaDB XAMPP (port 3306), database kehutanan_master.
#   Slave  = server MariaDB kedua (port 3307) yang datanya disimpan di ~/.kehutanan-replika (di luar repo).
#            Slave membaca binary log master dan menyalin setiap perubahan kehutanan_master secara otomatis ke
#            kehutanan_master miliknya sendiri (salinan identik, read_only). Website membaca salinan ini.
#            Database lain di master (staging, tabel login/sesi Laravel) tidak ikut disalin.
#
#   scripts/replika.sh setup    sekali di awal (dan setiap kali sql/schema.sql dijalankan ulang)
#   scripts/replika.sh start    nyalakan slave + arahkan website ke slave
#   scripts/replika.sh stop     arahkan website kembali ke mode sederhana + matikan slave
#   scripts/replika.sh status   status replikasi (SHOW SLAVE STATUS)
#
# Tanpa slave, proyek tetap berjalan: etl/replicate.py otomatis memakai mode script (simulasi di satu server).
# =====================================================================
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
XAMPP="${XAMPP_DIR:-/Applications/XAMPP/xamppfiles}"
[ -d "$XAMPP" ] || XAMPP=/opt/lampp
# data slave di luar folder proyek: mysql_install_db tidak tahan path yang mengandung spasi
DIR="${REPLIKA_DIR:-$HOME/.kehutanan-replika}"
CNF="$DIR/my.cnf"
PORT_MASTER=3306
PORT_SLAVE=3307
REPL_USER=replika
REPL_PASS="${REPLIKA_PASSWORD:-replika-lokal}"   # akun lokal khusus replikasi, hanya untuk 127.0.0.1
MYSQL="$XAMPP/bin/mysql"
MYSQLD="$XAMPP/sbin/mysqld"

# password root master diambil dari .env (XAMPP bawaan: kosong)
PASS_MASTER="$(grep -E '^DB_PASSWORD=' "$ROOT/.env" 2>/dev/null | cut -d= -f2- || true)"
master() { MYSQL_PWD="$PASS_MASTER" "$MYSQL" -h 127.0.0.1 -P "$PORT_MASTER" -u root "$@"; }
slave()  { "$MYSQL" -h 127.0.0.1 -P "$PORT_SLAVE" -u root "$@"; }
info()   { printf '\033[1;32m==>\033[0m %s\n' "$*"; }
gagal()  { printf '\033[1;31mGagal:\033[0m %s\n' "$*" >&2; exit 1; }

slave_hidup() { slave -e "SELECT 1" >/dev/null 2>&1; }

cek_master() {
    master -e "SELECT 1" >/dev/null 2>&1 || gagal "MariaDB XAMPP (port $PORT_MASTER) belum menyala. Nyalakan MySQL di XAMPP dulu."
    if [ "$(master -N -e 'SELECT @@log_bin')" != "1" ]; then
        cat >&2 <<PESAN

Binary log di MariaDB XAMPP (master) belum aktif. Jalankan sekali (butuh password Mac), lalu restart MySQL di XAMPP:

  sudo cp "$XAMPP/etc/my.cnf" "$XAMPP/etc/my.cnf.sebelum-replika"
  sudo perl -0pi -e 's/^#log-bin=mysql-bin\$/log-bin=mysql-bin\nbinlog_format=ROW/m' "$XAMPP/etc/my.cnf"

PESAN
        exit 1
    fi
}

tulis_cnf() {
    mkdir -p "$DIR"
    cat > "$CNF" <<CNF
[mysqld]
basedir=$XAMPP
datadir=$DIR/data
port=$PORT_SLAVE
bind-address=127.0.0.1
socket=$DIR/mysql.sock
pid-file=$DIR/mysqld.pid
log-error=$DIR/error.log
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
# identitas slave & aturan replikasi
server-id=2
read_only=1
relay-log=$DIR/data/relay-bin
replicate-do-db=kehutanan_master
CNF
}

nyalakan() {
    slave_hidup && return 0
    [ -d "$DIR/data/mysql" ] || gagal "Slave belum disiapkan. Jalankan: scripts/replika.sh setup"
    nohup "$MYSQLD" --defaults-file="$CNF" >/dev/null 2>&1 &
    for _ in $(seq 1 30); do slave_hidup && return 0; sleep 1; done
    gagal "Slave tidak mau menyala. Lihat log: $DIR/error.log"
}

atur_env() {  # atur_env NAMA NILAI -> tulis/ganti baris di web/.env
    local env="$ROOT/web/.env"
    if grep -q "^$1=" "$env"; then perl -pi -e "s/^$1=.*/$1=$2/" "$env"; else printf '%s=%s\n' "$1" "$2" >> "$env"; fi
}

arahkan_web() {  # sumber data website: "slave" (port 3307, kehutanan_master) atau "script" (koneksi utama)
    [ -f "$ROOT/web/.env" ] || return 0
    if [ "$1" = slave ]; then
        atur_env DATA_DB_PORT "$PORT_SLAVE"; atur_env DATA_DB_DATABASE kehutanan_master
        info "Website membaca data dari slave (port $PORT_SLAVE, kehutanan_master)"
    else
        atur_env DATA_DB_PORT ""; atur_env DATA_DB_DATABASE ""
        info "Website membaca data dari replika mode script (port $PORT_MASTER, kehutanan_dashboard)"
    fi
    (cd "$ROOT/web" && php artisan config:clear >/dev/null 2>&1 || true)
}

python_etl() { if [ -x "$ROOT/.venv/bin/python" ]; then echo "$ROOT/.venv/bin/python"; else echo python3; fi; }

perintah_setup() {
    cek_master
    if slave_hidup; then slave -e "SHUTDOWN"; sleep 3; fi   # konfigurasi selalu ditulis ulang
    tulis_cnf
    if [ ! -d "$DIR/data/mysql" ]; then
        info "Membuat server slave baru di $DIR"
        "$XAMPP/bin/mysql_install_db" --defaults-file="$CNF" --auth-root-authentication-method=normal \
            --skip-test-db >"$DIR/install.log" 2>&1 || gagal "mysql_install_db gagal, lihat $DIR/install.log"
    fi
    nyalakan

    info "Membuat akun replikasi di master"
    master -e "CREATE USER IF NOT EXISTS '$REPL_USER'@'127.0.0.1' IDENTIFIED BY '$REPL_PASS';
               CREATE USER IF NOT EXISTS '$REPL_USER'@'localhost' IDENTIFIED BY '$REPL_PASS';
               GRANT REPLICATION SLAVE ON *.* TO '$REPL_USER'@'127.0.0.1', '$REPL_USER'@'localhost';"

    info "Mengambil salinan awal kehutanan_master beserta posisi binary log-nya"
    MYSQL_PWD="$PASS_MASTER" "$XAMPP/bin/mysqldump" -h 127.0.0.1 -P "$PORT_MASTER" -u root \
        --single-transaction --master-data=2 kehutanan_master > "$DIR/salinan-awal.sql"
    local posisi berkas pos
    posisi="$(grep -m1 'CHANGE MASTER TO' "$DIR/salinan-awal.sql")"
    berkas="$(sed -E "s/.*MASTER_LOG_FILE='([^']+)'.*/\1/" <<<"$posisi")"
    pos="$(sed -E 's/.*MASTER_LOG_POS=([0-9]+).*/\1/' <<<"$posisi")"

    info "Memuat salinan awal ke slave lalu menyambungkan ke master ($berkas:$pos)"
    slave -e "STOP SLAVE; RESET SLAVE ALL;
              DROP DATABASE IF EXISTS kehutanan_dashboard;
              DROP DATABASE IF EXISTS kehutanan_master;
              CREATE DATABASE kehutanan_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    slave kehutanan_master < "$DIR/salinan-awal.sql"
    slave -e "CHANGE MASTER TO MASTER_HOST='127.0.0.1', MASTER_PORT=$PORT_MASTER,
                  MASTER_USER='$REPL_USER', MASTER_PASSWORD='$REPL_PASS',
                  MASTER_LOG_FILE='$berkas', MASTER_LOG_POS=$pos;
              START SLAVE;"
    sleep 2
    arahkan_web slave
    perintah_status
}

perintah_start() {
    cek_master
    nyalakan
    slave -e "START SLAVE" 2>/dev/null || true
    arahkan_web slave
    perintah_status
}

perintah_stop() {
    arahkan_web script
    if slave_hidup; then
        slave -e "SHUTDOWN"
        info "Slave dimatikan"
    fi
    info "Menyegarkan replika mode script (cadangan)"
    (cd "$ROOT/etl" && "$(python_etl)" replicate.py >/dev/null)
}

perintah_status() {
    if ! slave_hidup; then
        echo "Slave: MATI (port $PORT_SLAVE). Website memakai mode script."
        return 0
    fi
    slave -e "SHOW SLAVE STATUS\G" | grep -E ' (Master_Host|Master_Port|Slave_IO_Running|Slave_SQL_Running|Seconds_Behind_Master|Last_IO_Error|Last_SQL_Error|Replicate_Do_DB):'
}

case "${1:-status}" in
    setup)  perintah_setup ;;
    start)  perintah_start ;;
    stop)   perintah_stop ;;
    status) perintah_status ;;
    *) echo "Pakai: scripts/replika.sh setup|start|stop|status"; exit 1 ;;
esac
