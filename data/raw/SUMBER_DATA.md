# Sumber Data Mentah

Diunduh: 20 Oktober 2026. File di folder ini adalah file asli dari sumber, cuma diganti nama. **Jangan diedit.**

| Kode | File | Sumber | Link | Tahun tersedia | Diperbarui (sumber) |
|---|---|---|---|---|---|
| D1 | `xlsx/D1_kawasan_hutan_{2018..2023}.xlsx` | BPS | https://www.bps.go.id/id/statistics-table/3/Umpod09GaG5RbTlOYTJwaVFXWnJPRmQ1Wm1wMVp6MDkjMw==/luas-kawasan-hutan-dan-konservasi-perairan-sup--1--sup--menurut-provinsi--ha---2021.html?year=2021 (ganti `?year=` ke tahun yang diinginkan) | 2017–2023 | 2018: 3 Mar 2021 · 2019–2020: 23 Feb 2022 · 2021–2022: 22 Feb 2024 · 2023: 17 Feb 2025 |
| D2 | `csv/D2_tutupan_lahan_2014_2023.csv` | BPS (asal: KLHK, *Buku Rekalkulasi Penutupan Lahan Indonesia 2023*) | https://www.bps.go.id/id/statistics-table/1/MjExMCMx/rekapitulasi-luas-penutupan-lahan-hutan-dan-non-hutan-menurut-provinsi-tahun-2014-2022--ribu-ha-.html | 2014–2023 | 17 Feb 2025 |
| D3 | `xlsx/D3_karhutla_provinsi_2018_2026.xlsx` | SiPongi KLHK/Kemenhut (tab "List Data Provinsi") | https://sipongi.gakkum.kehutanan.go.id/indikasi-luas-kebakaran | 2018–2026 (2026 baru Jan–Jul) | data input s.d. Juli 2026 |
| D5 | `csv/D5_deforestasi_netto_2013_2022.csv` | BPS | https://www.bps.go.id/id/statistics-table/1/MjA4MSMx/angka-deforestasi-netto-indonesia-di-dalam-dan-di-luar-kawasan-hutan-tahun-2013-2022-ha-th-.html | periode 2013–2014 s.d. 2021–2022, per provinsi | 15 Jan 2024 |

Kewajiban sitasi SiPongi: "Sumber: SIPONGI KEMENHUT".

## Catatan untuk tahap transformasi
- **D1:** satuan tidak konsisten antartahun. Tabel 2018 angkanya benar-benar ribu ha (Aceh ±3.564). Tabel 2021 berlabel "Ribu Ha" tapi angkanya hektar (Aceh ±1.781.677). Cek tiap tahun satu per satu. Judul dan kolom 2023 juga beda ("menurut Provinsi dan Fungsi Hutan").
- **D2:** header bertingkat (tahun → Hutan/Non Hutan/Jumlah → luas/%). Kolom 2014–2022 memakai 34 provinsi. **2023 ada di blok terpisah di bawah dengan 38 provinsi**, jadi Papua perlu digabung balik ke 34. Pemisah ribuan pakai spasi ("3 156.7").
- **D2 (temuan saat ETL):** kolom **2020 tidak valid** — nilai provinsi tampak tertukar antarbaris (8 provinsi luas hutan > luas daratan, mis. Bali 5.656,6 ribu ha), sehingga 2020 dibuang. Kaltara baru dipisah mulai 2021 → digabung ke Kaltim untuk semua tahun. Lonjakan Jawa Tengah & Sumatera Selatan 2018→2019 ditandai perlu dicek.
- **D3:** sudah memuat kode wilayah (kode provinsi BPS). 2026 belum setahun penuh, jadi jangan dipakai di tren.
- **D5:** nilai per periode dua tahun, bisa negatif (reforestasi lebih besar dari deforestasi).
