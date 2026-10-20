<x-judul-panel judul="Analisis per data"
               sub="Satu baris per data: kiri tren nasional, kanan sebaran per provinsi. Tombol ⓘ di tiap kartu berisi penjelasan lengkap." />

{{-- Urutan mengikuti alur cerita: seharusnya (kawasan) → kenyataan (tutupan) → laju hilangnya hutan → dua penggerusnya.
     Dua kolom = satu baris per data. items-start: kartu tidak ikut memanjang saat tabel kartu sebelahnya dibuka. --}}
<div class="grid items-start gap-3 md:grid-cols-2">
    @include('dashboard.partials.kawasan')
    @include('dashboard.partials.tutupan')
    @include('dashboard.partials.deforestasi')
    @include('dashboard.partials.karhutla')
    @include('dashboard.partials.kayu')
    @include('dashboard.partials.catatan-data')
</div>
