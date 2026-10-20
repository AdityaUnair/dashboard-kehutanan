# Dashboard Kehutanan Indonesia

Tugas Integrasi Aplikasi Korporasi (Enterprise Application Integration) — Kelompok 3, S1 Teknologi Sains Data FTMM UNAIR.

> **Hutan Indonesia: berapa yang tersisa, dan apa yang menggerusnya?**
> Lima data kehutanan (BPS, KLHK/SiPongi) dalam empat format file berbeda diintegrasikan lewat pipeline ETL
> ke MariaDB, lalu ditampilkan di dashboard Laravel dengan login.

## Data

| Kode | Data | Sumber | Format | Cara extract |
|---|---|---|---|---|
| D1 | Tutupan lahan nasional menurut kelas, di dalam & di luar kawasan hutan (2018–2023) | BPS / KLHK | XLS | unduh file |
| D2 | Tutupan lahan hutan & non-hutan per provinsi (2018–2023) | BPS / KLHK | CSV | unduh file |
| D3 | Luas kebakaran hutan & lahan per provinsi (2018–2025, + 2026 Januari–Juli) | SiPongi KLHK | XLSX | unduh file |
| D5 | Deforestasi netto per provinsi (2017–2018 s.d. 2021–2022) | BPS / KLHK | CSV | unduh file |

Link sumber dan catatan tiap file: [`data/raw/SUMBER_DATA.md`](data/raw/SUMBER_DATA.md).

## Arsitektur (three-tier)

```
 data/raw (XLS, XLSX, CSV) ─┐
                        └──────────► kehutanan_staging ──────► kehutanan_master ──────► kehutanan_dashboard ──► Laravel (web/)
                                     (teks mentah)              (bersih, kode BPS)       (replika, dibaca web)
                                                                      ▲
```

| Teknik Data-level EAI | File | Bukti di dashboard |
|---|---|---|
| Transformation | `etl/transform_load.py` (aturan T1–T9), `etl/angka.py`, `etl/provinsi.py` | Log ETL tahap `transform-load` + cek total |
| Replication | Master-Slave MariaDB asli (`scripts/replika.sh`, opsional) atau simulasi `etl/replicate.py` (inkremental + checksum) | Kartu "Replikasi master → replika" (mode aktif, status slave, checksum) |

## Menjalankan

### Cara cepat (satu perintah)

Prasyarat: **XAMPP** (MySQL/MariaDB menyala), **Python 3.10+**, **PHP 8.2+**, **Composer**, **Node.js 20+**.

```bash
git clone https://github.com/AdityaUnair/dashboard-kehutanan.git
cd dashboard-kehutanan
./setup.sh                                          # macOS / Linux
# powershell -ExecutionPolicy Bypass -File setup.ps1   (Windows)
cd web && php artisan serve                         # buka http://localhost:8000
```

Script setup membuat 3 database dari `sql/schema.sql`, memasang library Python di `.venv`, menjalankan pipeline ETL dari file
mentah di `data/raw`, lalu menyiapkan Laravel (composer, npm build, key, migrasi, akun demo). Aman dijalankan ulang.

### Akun

Halaman login menampilkan akun demo dengan tombol **"Masuk sebagai …"** yang mengisi email & password otomatis
(daftarnya di `web/config/akun_demo.php`). Admin bisa mengelola akun (tambah, edit, hapus) lewat tombol **Akun** di kanan atas
dashboard; peran *pengguna* hanya melihat dashboard. Password akun disimpan sebagai hash bcrypt.

### Langkah manual (bila tidak memakai script)

```bash
cp .env.example .env && cp web/.env.example web/.env
/Applications/XAMPP/xamppfiles/bin/mysql -h 127.0.0.1 -u root < sql/schema.sql
python3 -m venv .venv && .venv/bin/pip install -r etl/requirements.txt
cd web && composer install && npm install && npm run build
php artisan key:generate && php artisan migrate --seed && php artisan serve
```

Gunakan klien `mysql` milik XAMPP: klien MySQL 9 (mis. dari Homebrew) tidak bisa login ke MariaDB XAMPP.

### Replikasi Master-Slave (opsional, untuk demo)

Tanpa langkah ini proyek tetap berjalan dengan **mode script**: `etl/replicate.py` menyalin perubahan `kehutanan_master` ke
`kehutanan_dashboard` di server yang sama (simulasi Master-Slave). Untuk replikasi **asli** MariaDB (slide TM 9: Master-Slave):

| | Master | Slave |
|---|---|---|
| Server | MariaDB XAMPP, port 3306 | MariaDB kedua, port 3307 (data di `~/.kehutanan-replika`) |
| Database | `kehutanan_master` (ditulis ETL & sinkronisasi) | salinan identik `kehutanan_master` (dibaca website, `read_only`) |
| Peran | mencatat setiap perubahan di *binary log* (`binlog_format=ROW`) | membaca binary log master dan menjalankan perubahan yang sama, terus-menerus |
| Disalin | – | hanya `kehutanan_master` (`replicate-do-db`); staging dan tabel login/sesi Laravel tidak ikut |

```bash
scripts/replika.sh setup     # sekali (macOS/Linux); menampilkan perintah sudo bila binary log XAMPP belum aktif,
                             # lalu restart MySQL: sudo /Applications/XAMPP/xamppfiles/xampp stopmysql && ... startmysql
scripts/replika.sh status    # SHOW SLAVE STATUS
scripts/replika.sh stop      # kembali ke mode script
scripts/replika.sh start     # nyalakan lagi (mis. setelah restart laptop)
```

Saat slave aktif, `etl/replicate.py` tidak menyalin apa pun: ia menunggu slave menyusul lalu membandingkan checksum master vs
slave. Website membaca data dari slave (`DATA_DB_PORT=3307`, `DATA_DB_DATABASE=kehutanan_master`, diatur otomatis oleh
script); tabel login/sesi Laravel tetap di server utama karena slave hanya boleh dibaca. Bila slave mati, dashboard otomatis memakai replika cadangan dan menandainya di
bagian Integrasi. Setelah menjalankan ulang `sql/schema.sql`, jalankan lagi `scripts/replika.sh setup`.

### Demo sinkronisasi untuk presentasi

```bash
cd etl
```

yang benar. Refresh dashboard sebelum dan sesudah Enter untuk melihat perubahannya. Pada mode Master-Slave perubahan di master
langsung tersalin ke slave tanpa menjalankan script replikasi.

## Struktur folder

```
dashboard-kehutanan/
├── data/raw/{xls,xlsx,csv,json}/  file asli dari sumber (jangan diedit)
├── etl/                        pipeline Python
├── scripts/replika.sh          server slave untuk replikasi Master-Slave (opsional)
├── setup.sh, setup.ps1         setup satu perintah (macOS/Linux, Windows)
├── sql/schema.sql              skema 3 database
└── web/                        Laravel
    ├── routes/web.php
    ├── app/Http/Controllers/   AuthController, DashboardController, AkunController (CRUD akun)
    ├── app/Models/             satu model per tabel
    ├── app/Services/           DashboardService (olah data untuk grafik)
    ├── resources/views/        auth/, dashboard/, dashboard/partials/, components/ (x-grafik, x-insight, x-judul-panel, x-ikon)
    ├── resources/css/app.css   Tailwind: token warna & komponen (kartu, insight, tabel)
    └── resources/js/dashboard.js  semua grafik Chart.js + tabel pendamping
```

Aturan visualisasi yang dipakai: satu sumbu per grafik (satuan berbeda dibandingkan lewat indeks 2018 = 100), warna = identitas
data dan konsisten di seluruh halaman (palet lolos uji buta warna), garis lurus untuk data tahunan, label langsung hanya di titik
penting, dan setiap grafik punya **tabel data** pendamping.

## Keterbatasan data (ringkas)

- D1 hanya tersedia di tingkat nasional (tanpa rincian provinsi), jadi tidak ada di peta sebaran.
- D2 dan D5 belum memisahkan Kalimantan Utara dari Kalimantan Timur; untuk analisis gabungan keduanya disatukan.
- Kolom 2020 pada tabel D2 dibuang oleh pemeriksaan ETL kami: 8 provinsi punya hutan > luas daratan (BPS tidak memberi
  catatan untuk tahun itu). Total nasional 2020-nya tetap cocok dengan D1.
- D5 baru tersedia s.d. periode 2021–2022. D3 2026 baru berisi Januari–Juli: ditampilkan pucat di grafik D3 dan peta,
  tetapi tidak dibandingkan dengan tahun penuh.
- D2 (rekalkulasi 2023) dan D5 (buku deforestasi per periode) tidak saling cocok; ditampilkan di bagian Validasi silang.
