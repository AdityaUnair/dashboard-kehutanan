{{-- Catatan kualitas data: mengisi 2 kolom terakhir baris keempat grid per data --}}
<div class="kartu md:col-span-2 xl:col-span-2 border-peringatan/30 bg-peringatan-lembut">
    <div class="flex items-center gap-2.5">
        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-surface text-peringatan"><x-ikon nama="lapisan" class="size-3.5" /></span>
        <div class="kartu-judul">Catatan data</div>
    </div>
    <div class="insight peringatan mt-3 border-0 bg-transparent p-0">
        <p><b>D2 · 2020</b><span>Tahun 2020 tidak digambar. BPS tidak memberi catatan untuk tahun itu, tetapi pemeriksaan ETL kami menemukan
            delapan provinsi dengan luas hutan lebih besar dari luas totalnya (mis. Bali 5,66 jt ha hutan dari 0,57 jt ha), sehingga 2020 dibuang.
            Ini kesimpulan kami, bukan pernyataan BPS. Lonjakan Jawa Tengah dan Sumatera Selatan 2018 → 2019 ditandai "perlu dicek" di log ETL.</span></p>
        <p><b>Kaltara</b><span>Keterangan BPS di tabel D2: "Data provinsi Kalimantan Timur merupakan data gabungan antara Provinsi Kalimantan
            Timur dan Kalimantan Utara". Kaltara baru terisi mulai 2021; kami menggabungkannya ke Kaltim untuk semua tahun agar deret waktunya
            konsisten. D1 dan D5 memang sudah gabungan dari sumbernya.</span></p>
        <p><b>D3 · 2026</b><span>Data kebakaran 2026 baru Januari–Juli, jadi tidak dibandingkan langsung dengan tahun penuh.</span></p>
    </div>
</div>
