"""TRANSFORM + LOAD: kehutanan_staging (teks mentah) -> kehutanan_master (data bersih).

Aturan transformasi utama:
  T1  Format angka diseragamkan (gaya Indonesia, Inggris, spasi) -> float           (angka.py)
  T2  Nama provinsi beragam -> kode BPS 2 digit; baris total nasional dipisahkan      (provinsi.py)
  T3  Provinsi hasil pemekaran Papua 2022 digabung ke induknya -> standar 34 provinsi
  T4  Satuan diseragamkan ke hektar (D1 2018-2019 & D2 tertulis ribu ha)
  T5  Struktur tabel lebar / header bertingkat -> format panjang (provinsi, tahun, nilai)
  T6  Filter periode 2018-2025; data 2026 (belum setahun) dibuang
  T7  Cek kualitas: jumlah provinsi dibandingkan dengan baris total nasional di sumber
  T8  Validasi logis D2: luas hutan > luas daratan = mustahil -> dibuang; lonjakan >25%/tahun ditandai
Load memakai UPSERT (insert kalau belum ada, update kalau sudah ada).
"""
import re
from collections import defaultdict

from angka import angka_indonesia, angka_inggris, angka_polos, angka_spasi
from config import DB_MASTER, DB_STAGING, TAHUN_MAKS, TAHUN_MIN, catat_etl, koneksi, run_id, sekarang
from provinsi import PROVINSI, kode_provinsi

CATATAN_KALTIM = "Termasuk Kalimantan Utara (sumber belum memisahkan)"


def ambil(conn, sql):
    with conn.cursor() as cur:
        cur.execute(sql)
        kolom = [c[0] for c in cur.description]
        return [dict(zip(kolom, r)) for r in cur.fetchall()]


def upsert(conn, tabel, kolom_kunci, baris):
    if not baris:
        return 0
    kolom = list(baris[0].keys())
    isian = ", ".join(["%s"] * len(kolom))
    nilai = [k for k in kolom if k not in kolom_kunci and k != "updated_at"]
    # updated_at ditulis PERTAMA agar dibandingkan dengan nilai lama: hanya berubah bila datanya berubah
    sama = " AND ".join(f"({k} <=> VALUES({k}))" for k in nilai)
    perbarui = ", ".join([f"updated_at=IF({sama}, updated_at, VALUES(updated_at))"] +
                         [f"{k}=VALUES({k})" for k in nilai])
    sql = (f"INSERT INTO {DB_MASTER}.{tabel} ({', '.join(kolom)}) VALUES ({isian}) "
           f"ON DUPLICATE KEY UPDATE {perbarui}")
    with conn.cursor() as cur:
        cur.executemany(sql, [tuple(b[k] for k in kolom) for b in baris])
    return len(baris)


def hapus_usang(conn, tabel, kolom_kunci, baris):
    """Hapus baris master yang tidak lagi dihasilkan transformasi (mis. data yang kini dinyatakan tidak valid)."""
    kunci_baru = {tuple(b[k] for k in kolom_kunci) for b in baris}
    with conn.cursor() as cur:
        cur.execute(f"SELECT {', '.join(kolom_kunci)} FROM {DB_MASTER}.{tabel}")
        usang = [k for k in cur.fetchall() if k not in kunci_baru]
        if usang:
            syarat = " AND ".join(f"{k}=%s" for k in kolom_kunci)
            cur.executemany(f"DELETE FROM {DB_MASTER}.{tabel} WHERE {syarat}", usang)
    return len(usang)


def cek_total(nama, per_kunci, total_sumber, toleransi=0.5):
    """Bandingkan jumlah semua provinsi dengan baris total di sumber (selisih dalam persen)."""
    hasil = []
    for kunci, total in sorted(total_sumber.items()):
        jumlah = per_kunci.get(kunci, 0)
        selisih = 0 if not total else abs(jumlah - total) / total * 100
        status = "OK" if selisih <= toleransi else "BEDA"
        hasil.append(f"{kunci}: {status} (selisih {selisih:.2f}%)")
    return f"Cek total {nama} -> " + "; ".join(hasil)


def _tambah(agregat, kunci, nilai):
    for k, v in nilai.items():
        if v is not None:
            agregat[kunci][k] = (agregat[kunci].get(k) or 0) + v


# ---------------------------------------------------------------- D1
def transform_d1(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d1_kawasan_hutan")
    total_per_tahun = {r["tahun_data"]: angka_polos(r["jumlah_raw"]) for r in rows
                       if kode_provinsi(r["provinsi_raw"]) == "TOTAL"}
    # T4: tabel lama berlabel "Ribu Ha" dan isinya memang ribuan; mulai 2020 isinya sudah hektar
    skala = {t: (1000 if tot < 1_000_000 else 1) for t, tot in total_per_tahun.items()}

    hasil, dibuang, jumlah = [], defaultdict(int), defaultdict(float)
    for r in rows:
        tahun, kode = r["tahun_data"], kode_provinsi(r["provinsi_raw"])
        if kode == "TOTAL":
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        if not (TAHUN_MIN <= tahun <= TAHUN_MAKS):
            dibuang["di luar periode"] += 1
            continue
        nilai = [angka_polos(r[k]) for k in ("hutan_lindung_raw", "konservasi_raw", "hpt_raw",
                                             "hp_raw", "hpk_raw", "jumlah_raw")]
        if all(v is None for v in nilai):
            dibuang["'...' (data masih digabung ke provinsi induk)"] += 1
            continue
        s = skala[tahun]
        hl, ks, hpt, hp, hpk, jml = [None if v is None else v * s for v in nilai]
        sk = r["tahun_sk_raw"]
        hasil.append({
            "kode_bps": kode, "tahun": tahun, "hutan_lindung_ha": hl, "konservasi_ha": ks,
            "produksi_terbatas_ha": hpt, "produksi_tetap_ha": hp, "produksi_konversi_ha": hpk,
            "total_ha": jml, "tahun_sk": int(sk) if sk and sk.isdigit() else None,
            "catatan": CATATAN_KALTIM if kode == "64" else None, "updated_at": sekarang(),
        })
        jumlah[tahun] += jml or 0
    total = {t: v * skala[t] for t, v in total_per_tahun.items() if TAHUN_MIN <= t <= TAHUN_MAKS}
    pesan = cek_total("D1", jumlah, total) + f" | skala ribu->ha dipakai: {[t for t, s in skala.items() if s > 1]}"
    return "fakta_kawasan_hutan", ("kode_bps", "tahun"), hasil, len(rows), dibuang, pesan


# ---------------------------------------------------------------- D2
def transform_d2(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d2_tutupan_lahan")
    agregat, dibuang, total = defaultdict(dict), defaultdict(int), {}
    for r in rows:
        tahun, kode = r["tahun_data"], kode_provinsi(r["provinsi_raw"])
        hutan, non, jml = (angka_spasi(r[k]) for k in ("hutan_raw", "non_hutan_raw", "jumlah_raw"))
        if kode == "TOTAL":
            total[tahun] = jml * 1000
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        if not (TAHUN_MIN <= tahun <= TAHUN_MAKS):
            dibuang["di luar periode"] += 1
            continue
        if kode == "65":
            # 2014-2022 sumber menggabung Kaltara ke Kaltim; 2023 juga digabung agar deret waktu konsisten
            kode = "64"
        # T4: ribu ha -> ha
        _tambah(agregat, (kode, tahun), {"hutan_ha": hutan * 1000, "non_hutan_ha": non * 1000, "total_ha": jml * 1000})

    # T8: validasi logis. Luas hutan tidak mungkin melebihi luas daratan provinsi.
    mustahil = defaultdict(list)
    for (kode, tahun), v in agregat.items():
        if v["hutan_ha"] > v["total_ha"]:
            mustahil[tahun].append(kode)
    tahun_rusak = sorted(t for t, kodes in mustahil.items() if len(kodes) >= 3)
    for (kode, tahun) in list(agregat):
        if tahun in tahun_rusak or kode in mustahil.get(tahun, []):
            del agregat[(kode, tahun)]
            dibuang[f"kolom {tahun} tidak valid (hutan > luas daratan)"] += 1

    # Lompatan > 25% dalam setahun ditandai untuk diperiksa (tidak dibuang)
    janggal = []
    for (kode, tahun), v in sorted(agregat.items()):
        lalu = agregat.get((kode, tahun - 1))
        if lalu and lalu["hutan_ha"] and abs(v["hutan_ha"] - lalu["hutan_ha"]) / lalu["hutan_ha"] > 0.25:
            janggal.append(f"{kode}/{tahun - 1}->{tahun}")

    hasil, jumlah = [], defaultdict(float)
    for (kode, tahun), v in sorted(agregat.items()):
        hasil.append({
            "kode_bps": kode, "tahun": tahun, "hutan_ha": round(v["hutan_ha"], 2),
            "non_hutan_ha": round(v["non_hutan_ha"], 2), "total_ha": round(v["total_ha"], 2),
            "persen_hutan": round(v["hutan_ha"] / v["total_ha"] * 100, 2) if v["total_ha"] else None,
            "catatan": CATATAN_KALTIM if kode == "64" else None, "updated_at": sekarang(),
        })
        jumlah[tahun] += v["total_ha"]
    total = {t: v for t, v in total.items() if TAHUN_MIN <= t <= TAHUN_MAKS and t not in tahun_rusak}
    pesan = cek_total("D2", jumlah, total)
    if tahun_rusak:
        pesan += f" | tahun dibuang: {tahun_rusak} (nilai provinsi tampak tertukar antarbaris di sumber)"
    if janggal:
        pesan += f" | perlu dicek (lonjakan >25%/tahun): {', '.join(janggal)}"
    return "fakta_tutupan_lahan", ("kode_bps", "tahun"), hasil, len(rows), dibuang, pesan


# ---------------------------------------------------------------- D3
def transform_d3(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d3_karhutla")
    agregat, dibuang, total = defaultdict(dict), defaultdict(int), {}
    for r in rows:
        tahun, kode, luas = r["tahun_data"], kode_provinsi(r["provinsi_raw"]), angka_indonesia(r["luas_raw"])
        if kode == "TOTAL":
            total[tahun] = luas
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        if tahun > TAHUN_MAKS:
            dibuang[f"tahun {tahun} belum lengkap (data s.d. Juli)"] += 1
            continue
        if tahun < TAHUN_MIN:
            dibuang["di luar periode"] += 1
            continue
        _tambah(agregat, (kode, tahun), {"luas_ha": luas})

    hasil, jumlah = [], defaultdict(float)
    for (kode, tahun), v in sorted(agregat.items()):
        hasil.append({"kode_bps": kode, "tahun": tahun, "luas_ha": round(v.get("luas_ha", 0), 2),
                      "updated_at": sekarang()})
        jumlah[tahun] += v.get("luas_ha", 0)
    total = {t: v for t, v in total.items() if TAHUN_MIN <= t <= TAHUN_MAKS}
    return "fakta_karhutla", ("kode_bps", "tahun"), hasil, len(rows), dibuang, cek_total("D3", jumlah, total)


# ---------------------------------------------------------------- D4
def transform_d4_rows(rows):
    agregat, dibuang, total = defaultdict(dict), defaultdict(int), {}
    for r in rows:
        tahun, kode, nilai = r["tahun_data"], kode_provinsi(r["provinsi_raw"]), angka_polos(r["nilai_raw"])
        if kode == "TOTAL":
            total[tahun] = nilai
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        if not (TAHUN_MIN <= tahun <= TAHUN_MAKS):
            dibuang["di luar periode"] += 1
            continue
        _tambah(agregat, (kode, tahun), {"produksi_m3": nilai})

    hasil, jumlah = [], defaultdict(float)
    for (kode, tahun), v in sorted(agregat.items()):
        hasil.append({"kode_bps": kode, "tahun": tahun, "produksi_m3": round(v.get("produksi_m3", 0), 2),
                      "updated_at": sekarang()})
        jumlah[tahun] += v.get("produksi_m3", 0)
    return hasil, dibuang, cek_total("D4", jumlah, total)


def transform_d4(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d4_produksi_kayu")
    hasil, dibuang, pesan = transform_d4_rows(rows)
    return "fakta_produksi_kayu", ("kode_bps", "tahun"), hasil, len(rows), dibuang, pesan


# ---------------------------------------------------------------- D5
def transform_d5(conn):
    rows = ambil(conn, f"SELECT * FROM {DB_STAGING}.stg_d5_deforestasi")
    agregat, dibuang, total = defaultdict(dict), defaultdict(int), {}
    for r in rows:
        kode = kode_provinsi(r["provinsi_raw"])
        awal, akhir = (int(x) for x in re.findall(r"\d{4}", r["periode_raw"]))
        periode = f"{awal}-{akhir}"
        dalam, luar, tot = (angka_inggris(r[k]) for k in ("dalam_kawasan_raw", "luar_kawasan_raw", "total_raw"))
        if kode == "TOTAL":
            total[periode] = tot
            continue
        if kode is None:
            dibuang["nama tidak dikenal"] += 1
            continue
        if akhir < TAHUN_MIN:
            dibuang["periode sebelum 2018"] += 1
            continue
        _tambah(agregat, (kode, periode, awal, akhir),
                {"dalam_kawasan_ha": dalam, "luar_kawasan_ha": luar, "total_ha": tot})

    hasil, jumlah = [], defaultdict(float)
    for (kode, periode, awal, akhir), v in sorted(agregat.items()):
        hasil.append({"kode_bps": kode, "periode": periode, "tahun_awal": awal, "tahun_akhir": akhir,
                      "dalam_kawasan_ha": v.get("dalam_kawasan_ha"), "luar_kawasan_ha": v.get("luar_kawasan_ha"),
                      "total_ha": v.get("total_ha"), "updated_at": sekarang()})
        jumlah[periode] += v.get("total_ha") or 0
    total = {p: v for p, v in total.items() if int(p[-4:]) >= TAHUN_MIN}
    return "fakta_deforestasi", ("kode_bps", "periode"), hasil, len(rows), dibuang, cek_total("D5", jumlah, total)


TRANSFORM = {"D1": transform_d1, "D2": transform_d2, "D3": transform_d3, "D4": transform_d4, "D5": transform_d5}


def load_provinsi(conn):
    baris = [{"kode_bps": k, "nama": n, "wilayah": w, "updated_at": sekarang()} for k, n, w in PROVINSI]
    return upsert(conn, "ref_provinsi", ("kode_bps",), baris)


def main(rid=None):
    rid = rid or run_id()
    conn = koneksi(DB_MASTER)
    try:
        n = load_provinsi(conn)
        catat_etl(conn, rid, "load", "REF", n, n, 0, "OK", "34 provinsi standar (kode BPS)")
        for dataset, fungsi in TRANSFORM.items():
            tabel, kunci, hasil, masuk, dibuang, pesan = fungsi(conn)
            dimuat = upsert(conn, tabel, kunci, hasil)
            terhapus = hapus_usang(conn, tabel, kunci, hasil)
            if terhapus:
                pesan += f" | {terhapus} baris lama dihapus dari master"
            alasan = "; ".join(f"{k}: {v}" for k, v in dibuang.items()) or "-"
            status = "PERIKSA" if "BEDA" in pesan else "OK"
            catat_etl(conn, rid, "transform-load", dataset, masuk, dimuat, sum(dibuang.values()), status,
                      f"Dibuang -> {alasan} | {pesan}")
            print(f"[transform-load] {dataset}: {masuk} baris staging -> {dimuat} baris {tabel} [{status}]")
            print(f"    dibuang: {alasan}")
            print(f"    {pesan}")
        conn.commit()
    finally:
        conn.close()
    return rid


if __name__ == "__main__":
    main()
