# Demo TRANSFORMATION untuk presentasi (Windows / PowerShell): data mentah (staging) vs data bersih (master).
#   powershell -ExecutionPolicy Bypass -File scripts\demo_transformasi.ps1
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$mysql = if ($env:MYSQL) { $env:MYSQL } elseif (Test-Path 'C:\xampp\mysql\bin\mysql.exe') { 'C:\xampp\mysql\bin\mysql.exe' } else { 'mysql' }
$env:MYSQL_PWD = ((Get-Content (Join-Path $root '.env') -ErrorAction SilentlyContinue | Where-Object { $_ -match '^DB_PASSWORD=' }) -replace '^DB_PASSWORD=', '')

function Q($sql) { & $mysql -h 127.0.0.1 -u root --default-character-set=utf8mb4 -t -e $sql }
function Judul($teks, $ket) { Write-Host "`n== $teks" -ForegroundColor Green; Write-Host $ket }
function Lanjut { Read-Host "`n[Enter] lanjut" | Out-Null }

Judul 'T1 + T4: format angka & satuan' 'Riau 2019 di tiga sumber: cara menulis angkanya beda-beda, D2 masih dalam RIBU ha.'
Q @"
SELECT 'D2 tutupan hutan' AS data, hutan_raw AS mentah_di_staging, 'ribu ha, spasi = ribuan' AS catatan FROM kehutanan_staging.stg_d2_tutupan_lahan WHERE provinsi_raw='RIAU' AND tahun_data=2019
UNION ALL SELECT 'D3 karhutla', luas_raw, 'titik = ribuan, koma = desimal' FROM kehutanan_staging.stg_d3_karhutla WHERE provinsi_raw='Riau' AND tahun_data=2019
UNION ALL SELECT 'D5 deforestasi 2018-2019', total_raw, 'koma = ribuan, titik = desimal' FROM kehutanan_staging.stg_d5_deforestasi WHERE provinsi_raw='Riau' AND periode_raw LIKE '2018%';
"@
Q @"
SELECT 'D2 tutupan hutan' AS data, hutan_ha AS bersih_di_master_ha FROM kehutanan_master.fakta_tutupan_lahan WHERE kode_bps='14' AND tahun=2019
UNION ALL SELECT 'D3 karhutla', luas_ha FROM kehutanan_master.fakta_karhutla WHERE kode_bps='14' AND tahun=2019
UNION ALL SELECT 'D5 deforestasi 2018-2019', total_ha FROM kehutanan_master.fakta_deforestasi WHERE kode_bps='14' AND periode='2018-2019';
"@
Lanjut

Judul 'T2: nama provinsi' 'Satu provinsi, tiga cara menulis (D4 sama dengan D2). Semuanya diganti kode BPS yang sama (19).'
Q @"
SELECT DISTINCT 'D2' AS data, provinsi_raw AS ditulis_di_sumber FROM kehutanan_staging.stg_d2_tutupan_lahan WHERE provinsi_raw LIKE '%BANGKA%'
UNION SELECT DISTINCT 'D3', provinsi_raw FROM kehutanan_staging.stg_d3_karhutla WHERE provinsi_raw LIKE '%Bangka%'
UNION SELECT DISTINCT 'D5', provinsi_raw FROM kehutanan_staging.stg_d5_deforestasi WHERE provinsi_raw LIKE '%Bangka%';
"@
Q "SELECT kode_bps, nama AS di_master FROM kehutanan_master.ref_provinsi WHERE kode_bps='19';"
Lanjut

Judul 'T3: pemekaran Papua' 'Sejak 2023 sumber D4 memakai 38 provinsi (Papua dipecah); di master digabung balik ke induknya (34 provinsi).'
Q "SELECT provinsi_raw AS ditulis_di_sumber_2025 FROM kehutanan_staging.stg_d4_produksi_kayu WHERE tahun_data=2025 AND provinsi_raw LIKE '%PAPUA%';"
Q @"
SELECT COUNT(DISTINCT provinsi_raw) - 1 AS provinsi_di_staging_2025, (SELECT COUNT(*) FROM kehutanan_master.fakta_produksi_kayu WHERE tahun=2025) AS provinsi_di_master_2025
FROM kehutanan_staging.stg_d4_produksi_kayu WHERE tahun_data=2025;
"@
Lanjut

Judul 'T7: cek total' 'Jumlah semua provinsi dibandingkan dengan baris total INDONESIA di file sumber (selisih harus 0%).'
Q @"
SELECT dataset, status, LEFT(SUBSTRING_INDEX(pesan, 'Cek total', -1), 110) AS hasil_cek_total FROM kehutanan_master.etl_log
WHERE tahap='transform-load' AND dataset<>'REF' AND run_id=(SELECT MAX(run_id) FROM kehutanan_master.etl_log) ORDER BY dataset;
"@
Write-Host "`nSelesai. Aturan lengkap T1-T8: docs\NASKAH_DEMO.md (Lampiran A)."
