# Dashboard Kehutanan Indonesia

Tugas Integrasi Aplikasi Korporasi (Enterprise Application Integration) — Kelompok 3, S1 Teknologi Sains Data FTMM UNAIR.

> **Hutan Indonesia: berapa yang tersisa, dan apa yang menggerusnya?**
> Lima data kehutanan (BPS, KLHK/SiPongi) dalam tiga format berbeda diintegrasikan lewat pipeline ETL
> ke MariaDB, lalu ditampilkan di dashboard Laravel dengan login.

## Data

| Kode | Data | Sumber | Format | Cara extract |
|---|---|---|---|---|
| D1 | Luas kawasan hutan per provinsi (2018–2023) | BPS | XLSX | unduh file |
| D2 | Tutupan lahan hutan & non-hutan per provinsi (2018–2023) | BPS / KLHK | CSV | unduh file |
| D3 | Luas kebakaran hutan & lahan per provinsi (2018–2025, + 2026 Januari–Juli) | SiPongi KLHK | XLSX | unduh file |
| D5 | Deforestasi netto per provinsi (2017–2018 s.d. 2021–2022) | BPS / KLHK | CSV | unduh file |

Link sumber dan catatan tiap file: [`data/raw/SUMBER_DATA.md`](data/raw/SUMBER_DATA.md).

## Arsitektur (three-tier)

```
 data/raw (XLSX, CSV)  ─┐
                        └──────────► kehutanan_staging ──────► kehutanan_master ──────► kehutanan_dashboard ──► Laravel (web/)
                                     (teks mentah)              (bersih, kode BPS)       (replika, dibaca web)
                                                                      ▲
```

| Teknik Data-level EAI | File | Bukti di dashboard |
|---|---|---|
| Transformation | `etl/transform_load.py` (aturan T1–T8), `etl/angka.py`, `etl/provinsi.py` | Log ETL tahap `transform-load` + cek total |
| Replication | `etl/replicate.py` (inkremental + checksum) | Tabel "Replikasi master → dashboard" |

## Menjalankan

Prasyarat: XAMPP (MariaDB menyala), Python 3.10+, PHP 8.2+, Composer, Node.js 20+.

Stack: **Python** (ETL) · **MariaDB** (XAMPP) · **Laravel 13** (MVC + Blade components) · **Tailwind CSS 4** · **Chart.js 4**, dibangun dengan **Vite**.

```bash
# 1. Konfigurasi
cp web/.env.example web/.env

# 2. Database
/Applications/XAMPP/xamppfiles/bin/mysql -h 127.0.0.1 -u root < sql/schema.sql

# 3. Pipeline ETL
pip install -r etl/requirements.txt
cd etl
cd ..

# 4. Website
cd web
composer install
npm install
npm run build                     # kompilasi Tailwind + Chart.js ke public/build
php artisan key:generate
php artisan migrate --seed        # tabel users/sessions + akun demo
php artisan serve                 # buka http://localhost:8000
```

Akun demo (hanya untuk lokal): lihat `web/database/seeders/DatabaseSeeder.php`.

### Demo sinkronisasi untuk presentasi

```bash
cd etl
```

Script mengubah produksi kayu Riau 2025 di master menjadi 0 (dan mereplikasinya), menunggu Enter, lalu memanggil

## Struktur folder

```
dashboard-kehutanan/
├── data/raw/{xlsx,csv,json}/   file asli dari sumber (jangan diedit)
├── etl/                        pipeline Python
├── sql/schema.sql              skema 3 database
└── web/                        Laravel
    ├── routes/web.php
    ├── app/Http/Controllers/   AuthController, DashboardController
    ├── app/Models/             satu model per tabel
    ├── app/Services/           DashboardService (olah data untuk grafik)
    ├── resources/views/        auth/, dashboard/, dashboard/partials/, components/ (x-bagian, x-grafik, x-insight)
    ├── resources/css/app.css   Tailwind: token warna & komponen (kartu, insight, tabel)
    └── resources/js/dashboard.js  semua grafik Chart.js + tabel pendamping
```

Aturan visualisasi yang dipakai: satu sumbu per grafik (satuan berbeda dibandingkan lewat indeks 2018 = 100), warna = identitas
data dan konsisten di seluruh halaman (palet lolos uji buta warna), garis lurus untuk data tahunan, label langsung hanya di titik
penting, dan setiap grafik punya **tabel data** pendamping.

## Keterbatasan data (ringkas)

- D1, D2, D5 belum memisahkan Kalimantan Utara dari Kalimantan Timur; untuk analisis gabungan keduanya disatukan.
- Kolom 2020 pada tabel D2 BPS tampak tertukar antarprovinsi (8 provinsi punya hutan > luas daratan) sehingga dibuang.
- D5 baru tersedia s.d. periode 2021–2022; D3 2026 belum setahun penuh sehingga tidak dipakai.
- D2 (rekalkulasi 2023) dan D5 (buku deforestasi per periode) tidak saling cocok; ditampilkan di bagian Validasi silang.
