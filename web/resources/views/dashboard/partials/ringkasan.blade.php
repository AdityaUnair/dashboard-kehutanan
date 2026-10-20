@use('App\Support\Format', 'F')

<section id="ringkasan">
    <div class="eyebrow">Integrasi 5 sumber data · 2018–2025</div>
    <h1 class="judul">Hutan Indonesia 2018–2025: berapa yang tersisa, dan apa yang menggerusnya?</h1>
    <p class="sub">
        Lima data kehutanan dari BPS dan KLHK dalam format berbeda (XLSX, CSV, JSON) diekstrak, diseragamkan, lalu dimuat
        ke satu database. Bagian 1–5 membahas tiap data; bagian gabungan menyambungkan semuanya.
    </p>

    <div class="kpi">
        <div class="kpi-item">
            <div class="kpi-label">Luas kawasan hutan {{ $kpi['kawasan']['tahun'] }}</div>
            <div class="kpi-nilai">{{ F::ringkas($kpi['kawasan']['nilai']) }} <small>ha</small></div>
            <div class="kpi-kaki">Status hukum (SK Menteri) · D1</div>
        </div>
        <div class="kpi-item" style="--warna: var(--teal)">
            <div class="kpi-label">Tutupan hutan {{ $kpi['tutupan']['tahun'] }}</div>
            <div class="kpi-nilai">{{ F::ringkas($kpi['tutupan']['nilai']) }} <small>ha</small></div>
            <div class="kpi-kaki">{{ F::persen($kpi['tutupan']['persen']) }} daratan ·
                <span class="{{ $kpi['tutupan']['perubahan'] >= 0 ? 'turun' : 'naik' }}">{{ F::persen($kpi['tutupan']['perubahan'], 1, true) }}</span> vs 2018 · D2</div>
        </div>
        <div class="kpi-item" style="--warna: var(--orange)">
            <div class="kpi-label">Deforestasi netto {{ $kpi['deforestasi']['periode'] }}</div>
            <div class="kpi-nilai">{{ F::ringkas($kpi['deforestasi']['nilai']) }} <small>ha</small></div>
            <div class="kpi-kaki"><span class="turun">{{ F::persen($kpi['deforestasi']['perubahan'], 0, true) }}</span> vs 2018–2019 · D5</div>
        </div>
        <div class="kpi-item" style="--warna: var(--fire)">
            <div class="kpi-label">Kebakaran hutan & lahan {{ $kpi['karhutla']['tahun'] }}</div>
            <div class="kpi-nilai">{{ F::ringkas($kpi['karhutla']['nilai']) }} <small>ha</small></div>
            <div class="kpi-kaki">Puncak {{ $kpi['karhutla']['puncak_tahun'] }}: {{ F::ringkas($kpi['karhutla']['puncak']) }} ha · D3</div>
        </div>
        <div class="kpi-item" style="--warna: var(--wood)">
            <div class="kpi-label">Produksi kayu bulat {{ $kpi['kayu']['tahun'] }}</div>
            <div class="kpi-nilai">{{ F::ringkas($kpi['kayu']['nilai']) }} <small>m³</small></div>
            <div class="kpi-kaki"><span class="naik">{{ F::persen($kpi['kayu']['perubahan'], 1, true) }}</span> vs 2018 · D4</div>
        </div>
    </div>
</section>
