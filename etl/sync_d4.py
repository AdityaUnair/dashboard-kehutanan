"""SYNCHRONIZATION (one-way): file sumber D4 (JSON BPS) -> kehutanan_master -> (replikasi) -> replika.

Sumber kebenaran D4 adalah file JSON dari BPS di data/raw/json (satu file per tahun). Bila BPS memperbarui
angkanya, file baru cukup diunduh ulang ke folder itu lalu script ini dijalankan. Script membaca file, menerapkan
transformasi yang sama dengan ETL, lalu membandingkan hasilnya dengan isi master baris per baris. Hanya baris baru
atau yang nilainya berubah yang di-upsert; perubahan di master tidak pernah ditulis balik ke file sumber (one-way).

Pemakaian:
  python sync_d4.py              # sinkronisasi biasa
  python sync_d4.py --simulasi   # demo: rusak 1 nilai di master dulu, lalu lihat sync memperbaikinya
"""
import json
import sys
from decimal import Decimal

import replicate
from config import DB_MASTER, TAHUN_MAKS, koneksi, sekarang
from extract import extract_d4
from transform_load import transform_d4_rows, upsert

KOLOM_STAGING = ["sumber_file", "datacontent_key", "vervar_val", "provinsi_raw", "tahun_data", "nilai_raw"]


def baca_sumber():
    """Isi terbaru file sumber D4, dalam bentuk baris staging (sama seperti tahap extract)."""
    return [dict(zip(KOLOM_STAGING, r)) for r in extract_d4()]


def simulasi_perubahan(conn):
    with conn.cursor() as cur:
        cur.execute(f"UPDATE {DB_MASTER}.fakta_produksi_kayu SET produksi_m3 = 0, updated_at = %s "
                    "WHERE kode_bps = '14' AND tahun = %s", (sekarang(), TAHUN_MAKS))
    conn.commit()
    replicate.main()
    print(f"[simulasi] Produksi kayu Riau {TAHUN_MAKS} di master diubah jadi 0 dan sudah tereplikasi ke dashboard.")
    input("[simulasi] Cek dashboard (angka Riau berubah), lalu tekan Enter untuk menjalankan sync... ")


def main():
    conn = koneksi(DB_MASTER)
    try:
        if "--simulasi" in sys.argv:
            simulasi_perubahan(conn)

        hasil_sumber, _, _ = transform_d4_rows(baca_sumber())
        with conn.cursor() as cur:
            cur.execute("SELECT kode_bps, tahun, produksi_m3 FROM fakta_produksi_kayu")
            di_master = {(k, t): v for k, t, v in cur.fetchall()}

        baru, berubah, sama, detail = [], [], 0, []
        for b in hasil_sumber:
            kunci = (b["kode_bps"], b["tahun"])
            nilai_sumber = Decimal(str(b["produksi_m3"])).quantize(Decimal("0.01"))
            if kunci not in di_master:
                baru.append(b)
                detail.append({"kode_bps": kunci[0], "tahun": kunci[1], "lama": None, "baru": float(nilai_sumber)})
            elif di_master[kunci] != nilai_sumber:
                berubah.append(b)
                detail.append({"kode_bps": kunci[0], "tahun": kunci[1],
                               "lama": float(di_master[kunci]), "baru": float(nilai_sumber)})
            else:
                sama += 1

        upsert(conn, "fakta_produksi_kayu", ("kode_bps", "tahun"), baru + berubah)
        with conn.cursor() as cur:
            cur.execute(
                "INSERT INTO sync_log (dataset, baris_dicek, baris_baru, baris_berubah, baris_sama, detail, waktu) "
                "VALUES (%s,%s,%s,%s,%s,%s,%s)",
                ("D4", len(hasil_sumber), len(baru), len(berubah), sama, json.dumps(detail), sekarang()),
            )
        conn.commit()
        print(f"[sync] D4: dicek {len(hasil_sumber)} | baru {len(baru)} | berubah {len(berubah)} | sama {sama}")
        for d in detail:
            print(f"       {d['kode_bps']} {d['tahun']}: {d['lama']} -> {d['baru']}")
    finally:
        conn.close()

    replicate.main()


if __name__ == "__main__":
    main()
