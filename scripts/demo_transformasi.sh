#!/usr/bin/env bash
# Demo TRANSFORMATION untuk presentasi: data mentah (staging) vs data bersih (master), berdampingan.
#   scripts/demo_transformasi.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MYSQL=""
for k in /Applications/XAMPP/xamppfiles/bin/mysql /opt/lampp/bin/mysql "$(command -v mariadb || true)" "$(command -v mysql || true)"; do
    [ -z "$MYSQL" ] && [ -n "$k" ] && [ -x "$k" ] && MYSQL="$k"
done
PASS="$(grep -E '^DB_PASSWORD=' "$ROOT/.env" 2>/dev/null | cut -d= -f2- || true)"
q() { MYSQL_PWD="$PASS" "$MYSQL" -h 127.0.0.1 -u root -t -e "$1"; }
judul() { printf '\n\033[1;32m== %s\033[0m\n%s\n' "$1" "$2"; }
lanjut() { read -r -p $'\n[Enter] lanjut... ' _; }

judul "T1 + T4: format angka & satuan" "Riau 2019 di tiga sumber: cara menulis angkanya beda-beda, D2 masih dalam RIBU ha."
q "SELECT 'D2 tutupan hutan' AS data, hutan_raw AS mentah_di_staging, 'ribu ha, spasi = ribuan' AS catatan FROM kehutanan_staging.stg_d2_tutupan_lahan WHERE provinsi_raw='RIAU' AND tahun_data=2019
   UNION ALL SELECT 'D3 karhutla', luas_raw, 'titik = ribuan, koma = desimal' FROM kehutanan_staging.stg_d3_karhutla WHERE provinsi_raw='Riau' AND tahun_data=2019
   UNION ALL SELECT 'D5 deforestasi 2018-2019', total_raw, 'koma = ribuan, titik = desimal' FROM kehutanan_staging.stg_d5_deforestasi WHERE provinsi_raw='Riau' AND periode_raw LIKE '2018%';"
q "SELECT 'D2 tutupan hutan' AS data, hutan_ha AS bersih_di_master_ha FROM kehutanan_master.fakta_tutupan_lahan WHERE kode_bps='14' AND tahun=2019
   UNION ALL SELECT 'D3 karhutla', luas_ha FROM kehutanan_master.fakta_karhutla WHERE kode_bps='14' AND tahun=2019
   UNION ALL SELECT 'D5 deforestasi 2018-2019', total_ha FROM kehutanan_master.fakta_deforestasi WHERE kode_bps='14' AND periode='2018-2019';"
lanjut

judul "T2: nama provinsi" "Satu provinsi, tiga cara menulis (D4 sama dengan D2). Semuanya diganti kode BPS yang sama (19)."
q "SELECT DISTINCT 'D2' AS data, provinsi_raw AS ditulis_di_sumber FROM kehutanan_staging.stg_d2_tutupan_lahan WHERE provinsi_raw LIKE '%BANGKA%'
   UNION SELECT DISTINCT 'D3', provinsi_raw FROM kehutanan_staging.stg_d3_karhutla WHERE provinsi_raw LIKE '%Bangka%'
   UNION SELECT DISTINCT 'D5', provinsi_raw FROM kehutanan_staging.stg_d5_deforestasi WHERE provinsi_raw LIKE '%Bangka%';"
q "SELECT kode_bps, nama AS di_master FROM kehutanan_master.ref_provinsi WHERE kode_bps='19';"
lanjut

judul "T3: pemekaran Papua" "Sejak 2023 sumber D4 memakai 38 provinsi (Papua dipecah); di master digabung balik ke induknya (34 provinsi)."
q "SELECT provinsi_raw AS ditulis_di_sumber_2025 FROM kehutanan_staging.stg_d4_produksi_kayu WHERE tahun_data=2025 AND provinsi_raw LIKE '%PAPUA%';"
q "SELECT COUNT(DISTINCT provinsi_raw) - 1 AS provinsi_di_staging_2025, (SELECT COUNT(*) FROM kehutanan_master.fakta_produksi_kayu WHERE tahun=2025) AS provinsi_di_master_2025
   FROM kehutanan_staging.stg_d4_produksi_kayu WHERE tahun_data=2025;"
lanjut

judul "T7: cek total" "Jumlah semua provinsi dibandingkan dengan baris total INDONESIA di file sumber (selisih harus 0%)."
q "SELECT dataset, status, LEFT(SUBSTRING_INDEX(pesan, 'Cek total', -1), 110) AS hasil_cek_total FROM kehutanan_master.etl_log
   WHERE tahap='transform-load' AND dataset<>'REF' AND run_id=(SELECT MAX(run_id) FROM kehutanan_master.etl_log) ORDER BY dataset;"
lanjut

judul "T8: cek masuk akal (D2 2020)" "Bali 2020: luas hutan lebih besar dari luas daratannya. Mustahil, jadi kolom 2020 D2 dibuang."
q "SELECT provinsi_raw, tahun_data, hutan_raw AS hutan_ribu_ha, jumlah_raw AS daratan_ribu_ha FROM kehutanan_staging.stg_d2_tutupan_lahan WHERE provinsi_raw='Bali' AND tahun_data IN (2019, 2020);"
printf '\nSelesai. Aturan lengkap T1-T9: docs/NASKAH_DEMO.md (Lampiran A).\n'
