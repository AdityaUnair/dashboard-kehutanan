"""REPLICATION: kehutanan_master -> kehutanan_dashboard (replika yang dibaca website).

Mode dipilih otomatis:
  1. MASTER-SLAVE (replikasi bawaan MariaDB), bila server slave (port 3307, lihat scripts/replika.sh) aktif.
     MariaDB XAMPP (master) mencatat setiap perubahan di binary log; server slave membacanya dan menjalankan perubahan
     yang sama pada salinan kehutanan_master miliknya secara terus-menerus (website membaca salinan ini). Script ini
     TIDAK menyalin apa pun: hanya menunggu slave menyusul, lalu membandingkan jumlah baris & CHECKSUM TABLE.
  2. SCRIPT (simulasi Master-Slave di satu server), bila slave tidak aktif. Replikasi inkremental berbasis primary key:
       - baris yang ada di master tapi belum ada di replika  -> disisipkan
       - baris yang isinya berbeda                           -> diperbarui
       - baris yang sudah tidak ada di master                -> dihapus dari replika
     lalu CHECKSUM TABLE kedua sisi dibandingkan.
Hasil kedua mode dicatat di kehutanan_master.replication_log, sehingga ikut tersalin ke replika dan tampil di dashboard.
"""
from config import DB_DASHBOARD, DB_MASTER, REPLIKA_PORT, koneksi, koneksi_slave, sekarang

TABEL = {
    "ref_provinsi": ("kode_bps",),
    "fakta_tutupan_kelas": ("kode_kelas", "tahun"),
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


def _hitung(cur, db, tabel):
    cur.execute(f"SELECT COUNT(*) FROM {db}.{tabel}")
    jumlah = cur.fetchone()[0]
    cur.execute(f"CHECKSUM TABLE {db}.{tabel}")
    return jumlah, cur.fetchone()[1]


def _catat(cur, mode, tabel, n_master, n_replika, baru, berubah, hilang, cs_master, cs_replika):
    status = "SINKRON" if cs_master == cs_replika and n_master == n_replika else "BEDA"
    cur.execute(
        f"INSERT INTO {DB_MASTER}.replication_log (mode, tabel, baris_master, baris_replika, baris_disisipkan, "
        "baris_diperbarui, baris_dihapus, checksum_master, checksum_replika, status, waktu) "
        "VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
        (mode, tabel, n_master, n_replika, baru, berubah, hilang, cs_master, cs_replika, status, sekarang()),
    )
    return status


def salin_tabel(cur, tabel, kunci):
    """Mode script: samakan isi replika dengan master (sisipkan / perbarui / hapus)."""
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
    return len(baru), len(berubah), len(hilang)


def mode_script(conn):
    with conn.cursor() as cur:
        for tabel, kunci in TABEL.items():
            baru, berubah, hilang = salin_tabel(cur, tabel, kunci)
            (n_m, cs_m), (n_r, cs_r) = _hitung(cur, DB_MASTER, tabel), _hitung(cur, DB_DASHBOARD, tabel)
            status = _catat(cur, "script", tabel, n_m, n_r, baru, berubah, hilang, cs_m, cs_r)
            print(f"[replicate:script] {tabel:22} master={n_m:4}  +{baru:<4} ~{berubah:<4} -{hilang:<4} {status}")
        salin_tabel(cur, "replication_log", ("id",))  # catatan replikasi ikut disalin
    conn.commit()


def mode_master_slave(conn, slave):
    with conn.cursor() as cur, slave.cursor() as cs:
        # tunggu slave selesai menjalankan semua perubahan yang sudah ada di binary log master (maks. 30 detik)
        cur.execute("SHOW MASTER STATUS")
        posisi_master = cur.fetchone()
        if not posisi_master:
            raise SystemExit("Binary log master (XAMPP) belum aktif. Lihat README bagian Mode Master-Slave.")
        berkas, posisi = posisi_master[:2]
        cs.execute("SELECT MASTER_POS_WAIT(%s, %s, 30)", (berkas, posisi))
        if cs.fetchone()[0] in (None, -1):
            print("[replicate:master-slave] PERINGATAN: slave belum menyusul dalam 30 detik")
        for tabel in TABEL:
            # slave menyimpan salinan dengan nama database yang sama
            (n_m, cs_m), (n_r, cs_r) = _hitung(cur, DB_MASTER, tabel), _hitung(cs, DB_MASTER, tabel)
            status = _catat(cur, "master-slave", tabel, n_m, n_r, None, None, None, cs_m, cs_r)
            print(f"[replicate:master-slave] {tabel:22} master={n_m:4} slave={n_r:4} {status}")
    conn.commit()  # catatan ini sendiri ikut tersalin ke slave lewat binary log


def slave_aktif(slave):
    with slave.cursor() as cs:
        cs.execute("SHOW SLAVE STATUS")
        baris = cs.fetchone()
        if not baris:
            return False
        kolom = [c[0] for c in cs.description]
        st = dict(zip(kolom, baris))
        return st["Slave_IO_Running"] == "Yes" and st["Slave_SQL_Running"] == "Yes"


def main():
    conn = koneksi()
    slave = koneksi_slave()
    try:
        if slave and slave_aktif(slave):
            print(f"=== replikasi: MASTER-SLAVE (slave aktif di port {REPLIKA_PORT}) ===")
            mode_master_slave(conn, slave)
        else:
            alasan = "tidak aktif" if not slave else "aktif tetapi thread replikasinya berhenti"
            print(f"=== replikasi: SCRIPT (server slave port {REPLIKA_PORT} {alasan}) ===")
            mode_script(conn)
    finally:
        conn.close()
        if slave:
            slave.close()


if __name__ == "__main__":
    main()
