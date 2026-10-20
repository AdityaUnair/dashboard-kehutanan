<x-judul-panel judul="Analisis per data"
               sub="Kelima data tampil sekaligus, dua grafik per data: tren nasional dan sebaran per provinsi. Tombol ⓘ di tiap kartu berisi penjelasan lengkap." />

{{-- Urutan mengikuti alur cerita: seharusnya (kawasan) → kenyataan (tutupan) → laju hilangnya hutan → dua penggerusnya --}}
<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
    @include('dashboard.partials.kawasan')
    @include('dashboard.partials.tutupan')
    @include('dashboard.partials.deforestasi')
    @include('dashboard.partials.karhutla')
    @include('dashboard.partials.kayu')
    @include('dashboard.partials.catatan-data')
</div>
