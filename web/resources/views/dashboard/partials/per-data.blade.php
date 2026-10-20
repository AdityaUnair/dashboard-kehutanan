<x-judul-panel judul="Analisis per data"
               sub="Pilih satu data untuk melihat tren dan sebarannya per provinsi. Urutannya mengikuti alur cerita: seharusnya (kawasan) → kenyataan (tutupan) → laju hilangnya hutan → dua penggerusnya.">
    <div>
        <label for="pilihData" class="label-kontrol">Data yang ditampilkan</label>
        <select id="pilihData" class="pilih min-w-72">
            <option value="D1">1 · Kawasan hutan (D1 · BPS · XLSX)</option>
            <option value="D2">2 · Tutupan hutan (D2 · BPS/KLHK · CSV)</option>
            <option value="D5">3 · Deforestasi netto (D5 · BPS/KLHK · CSV)</option>
            <option value="D3">4 · Kebakaran hutan & lahan (D3 · SiPongi · XLSX)</option>
        </select>
    </div>
</x-judul-panel>

@include('dashboard.partials.kawasan')
@include('dashboard.partials.tutupan')
@include('dashboard.partials.deforestasi')
@include('dashboard.partials.karhutla')
@include('dashboard.partials.kayu')
