import os
from datetime import datetime
from pathlib import Path

import pymysql
from dotenv import load_dotenv

ROOT = Path(__file__).resolve().parent.parent
RAW_DIR = ROOT / "data" / "raw"
load_dotenv(ROOT / ".env")

DB_STAGING = "kehutanan_staging"
DB_MASTER = "kehutanan_master"
DB_DASHBOARD = "kehutanan_dashboard"

TAHUN_MIN, TAHUN_MAKS = 2018, 2025

# Server slave untuk replikasi Master-Slave (opsional, lihat scripts/replika.sh)
REPLIKA_HOST = os.getenv("REPLIKA_HOST", "127.0.0.1")
REPLIKA_PORT = int(os.getenv("REPLIKA_PORT", "3307"))


def koneksi(database=None):
    return pymysql.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=int(os.getenv("DB_PORT", "3306")),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=database,
        charset="utf8mb4",
        autocommit=False,
    )


def koneksi_slave(timeout=2):
    """Koneksi ke server slave (root tanpa password, hanya mendengar 127.0.0.1). None bila slave tidak aktif."""
    try:
        return pymysql.connect(host=REPLIKA_HOST, port=REPLIKA_PORT, user="root", password="",
                               charset="utf8mb4", connect_timeout=timeout, autocommit=True)
    except pymysql.err.OperationalError:
        return None


def sekarang():
    return datetime.now().replace(microsecond=0)


def run_id():
    return datetime.now().strftime("%Y%m%d-%H%M%S")


def catat_etl(conn, rid, tahap, dataset, masuk, dimuat, dibuang, status, pesan=""):
    with conn.cursor() as cur:
        cur.execute(
            f"INSERT INTO {DB_MASTER}.etl_log (run_id, tahap, dataset, baris_masuk, baris_dimuat, "
            "baris_dibuang, status, pesan, waktu) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
            (rid, tahap, dataset, masuk, dimuat, dibuang, status, pesan, sekarang()),
        )
