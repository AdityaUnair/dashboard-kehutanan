"""REPLICATION: kehutanan_master (sumber) -> kehutanan_dashboard (replika yang dibaca website).

Replikasi inkremental berbasis primary key:
  - baris yang ada di master tapi belum ada di replika  -> disisipkan
  - baris yang isinya berbeda                           -> diperbarui
  - baris yang sudah tidak ada di master                -> dihapus dari replika
Setelah itu CHECKSUM TABLE kedua sisi dibandingkan; sama = replika identik dengan master.
"""
from config import DB_DASHBOARD, DB_MASTER, koneksi, sekarang

TABEL = {
    "ref_provinsi": ("kode_bps",),
    "fakta_kawasan_hutan": ("kode_bps", "tahun"),
    "fakta_tutupan_lahan": ("kode_bps", "tahun"),
    "fakta_karhutla": ("kode_bps", "tahun"),
    "fakta_produksi_kayu": ("kode_bps", "tahun"),
    "fakta_deforestasi": ("kode_bps", "periode"),
    "etl_log": ("id",),
    "sync_log": ("id",),
}


def _semua_baris(cur, db, tabel, kunci):
    cur.execute(f"SELECT * FROM {db}.{tabel}")
    kolom = [c[0] for c in cur.description]
    idx = [kolom.index(k) for k in kunci]
    return kolom, {tuple(r[i] for i in idx): r for r in cur.fetchall()}


def _checksum(cur, db, tabel):
    cur.execute(f"CHECKSUM TABLE {db}.{tabel}")
    return cur.fetchone()[1]


def replikasi_tabel(cur, tabel, kunci):
    kolom, master = _semua_baris(cur, DB_MASTER, tabel, kunci)
    _, replika = _semua_baris(cur, DB_DASHBOARD, tabel, kunci)

    baru = [master[k] for k in master.keys() - replika.keys()]
    berubah = [master[k] for k in master.keys() & replika.keys() if master[k] != replika[k]]
    hilang = list(replika.keys() - master.keys())

    daftar_kolom = ", ".join(kolom)
    isian = ", ".join(["%s"] * len(kolom))
    if baru or berubah:
        cur.executemany(f"REPLACE INTO {DB_DASHBOARD}.{tabel} ({daftar_kolom}) VALUES ({isian})", baru + berubah)
    if hilang:
        syarat = " AND ".join(f"{k}=%s" for k in kunci)
        cur.executemany(f"DELETE FROM {DB_DASHBOARD}.{tabel} WHERE {syarat}", hilang)

    cs_master, cs_replika = _checksum(cur, DB_MASTER, tabel), _checksum(cur, DB_DASHBOARD, tabel)
    status = "SINKRON" if cs_master == cs_replika else "BEDA"
    cur.execute(
        f"INSERT INTO {DB_DASHBOARD}.replication_log (tabel, baris_master, baris_disisipkan, baris_diperbarui, "
        "baris_dihapus, checksum_master, checksum_replika, status, waktu) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)",
        (tabel, len(master), len(baru), len(berubah), len(hilang), cs_master, cs_replika, status, sekarang()),
    )
    return len(master), len(baru), len(berubah), len(hilang), status


def main():
    conn = koneksi()
    try:
        with conn.cursor() as cur:
            for tabel, kunci in TABEL.items():
                total, baru, berubah, hilang, status = replikasi_tabel(cur, tabel, kunci)
                print(f"[replicate] {tabel:22} master={total:4}  +{baru:<4} ~{berubah:<4} -{hilang:<4} {status}")
        conn.commit()
    finally:
        conn.close()


if __name__ == "__main__":
    main()
