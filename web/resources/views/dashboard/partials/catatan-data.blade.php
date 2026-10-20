{{-- Catatan kualitas data: satu baris penuh di akhir grid per data --}}
<div class="kartu md:col-span-2 border-peringatan/30 bg-peringatan-lembut">
    <div class="flex items-center gap-2.5">
        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-surface text-peringatan"><x-ikon nama="lapisan" class="size-3.5" /></span>
        <div class="kartu-judul">Catatan data</div>
    </div>
    <div class="insight peringatan mt-3 border-0 bg-transparent p-0">
        <p><b>Kaltara</b><span>Keterangan BPS di tabel D2: "Data provinsi Kalimantan Timur merupakan data gabungan antara Provinsi Kalimantan
            Timur dan Kalimantan Utara". Kaltara baru terisi mulai 2021; kami menggabungkannya ke Kaltim untuk semua tahun agar deret waktunya
            konsisten. D5 memang sudah gabungan dari sumbernya.</span></p>
        <p><b>D1 · nasional</b><span>Tabel kelas lahan dari BPS hanya tersedia untuk tingkat nasional, jadi D1 tidak punya rincian provinsi dan
            tidak ada di peta. Total hutannya cocok dengan baris total nasional D2 di setiap tahun (dicek otomatis oleh ETL).</span></p>
        <p id="catatan-validasi" class="scroll-mt-28"><b>D2 vs D5</b><span>Dua data resmi ini tidak cocok: {{ $validasi['periode'] }} tutupan hutan (D2) naik
            {{ \App\Support\Format::ringkas($validasi['total_d2']) }} ha, padahal deforestasi netto (D5) mencatat {{ \App\Support\Format::ringkas($validasi['hilang_d5']) }} ha hutan hilang.
            Rincian D1 menunjukkan kenaikannya datang dari hutan sekunder (+{{ \App\Support\Format::ringkas($validasi['jenis_d1']['sekunder']) }}) dan hutan tanaman
            (+{{ \App\Support\Format::ringkas($validasi['jenis_d1']['tanaman']) }}). Dugaan kami: cara hitungnya berbeda (D1 & D2 dari buku Rekalkulasi Penutupan Lahan,
            D5 dari buku deforestasi per periode).</span></p>
        <p><b>D3 · 2026</b><span>Data kebakaran 2026 baru Januari–Juli, jadi tidak dibandingkan langsung dengan tahun penuh.</span></p>
    </div>
</div>
