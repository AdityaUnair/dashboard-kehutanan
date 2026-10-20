@use('App\Support\Format', 'F')

@php
    $tiles = [
        ['Luas kawasan hutan '.$kpi['kawasan']['tahun'], F::ringkas($kpi['kawasan']['nilai']), 'ha',
            'Status hukum (SK Menteri)', null, 'D1'],
        ['Tutupan hutan '.$kpi['tutupan']['tahun'], F::ringkas($kpi['tutupan']['nilai']), 'ha',
            F::persen($kpi['tutupan']['persen']).' daratan', [$kpi['tutupan']['perubahan'], 'vs 2018', true], 'D2'],
        ['Deforestasi netto '.$kpi['deforestasi']['periode'], F::ringkas($kpi['deforestasi']['nilai']), 'ha/th',
            null, [$kpi['deforestasi']['perubahan'], 'vs 2018–2019', false], 'D5'],
        ['Kebakaran hutan & lahan '.$kpi['karhutla']['tahun'], F::ringkas($kpi['karhutla']['nilai']), 'ha',
            'Puncak '.$kpi['karhutla']['puncak_tahun'].': '.F::ringkas($kpi['karhutla']['puncak']).' ha', null, 'D3'],
        ['Produksi kayu bulat '.$kpi['kayu']['tahun'], F::ringkas($kpi['kayu']['nilai']), 'm³',
            null, [$kpi['kayu']['perubahan'], 'vs 2018', null], 'D4'],
    ];
@endphp

<section id="ringkasan" class="scroll-mt-6">
    <p class="text-[11px] font-semibold tracking-[0.18em] text-brand uppercase">Integrasi 5 sumber data · 2018–2025</p>
    <h1 class="mt-2 max-w-4xl text-[26px] leading-tight font-bold tracking-tight md:text-[32px]">
        Hutan Indonesia 2018–2025: berapa yang tersisa, dan apa yang menggerusnya?
    </h1>
    <p class="mt-3 max-w-3xl text-[15px] text-ink-2">
        Lima data kehutanan dari BPS dan KLHK dalam tiga format (XLSX, CSV, JSON) diekstrak, diseragamkan, lalu dimuat ke satu
        database. Bagian 1–5 membahas tiap data; bagian gabungan menyambungkan semuanya.
    </p>

    <div class="mt-7 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
        @foreach ($tiles as [$label, $nilai, $satuan, $catatan, $delta, $kode])
            <div class="kartu p-4">
                <div class="flex items-start justify-between gap-2 text-xs font-medium text-ink-2">
                    <span>{{ $label }}</span><span class="text-[10.5px] font-semibold text-ink-3">{{ $kode }}</span>
                </div>
                <div class="mt-2 text-[26px] leading-none font-bold tracking-tight">
                    {{ $nilai }} <span class="text-sm font-medium text-ink-3">{{ $satuan }}</span>
                </div>
                <div class="mt-2 text-xs text-ink-3">
                    @if ($delta)
                        @php [$v, $teks, $baikNaik] = $delta; @endphp
                        <span @class([
                            'font-semibold',
                            'text-[#1f6b3a]' => $baikNaik === true ? $v > 0 : ($baikNaik === false ? $v < 0 : false),
                            'text-[#a33433]' => $baikNaik === true ? $v < 0 : ($baikNaik === false ? $v > 0 : false),
                            'text-ink-2' => $baikNaik === null,
                        ])>{{ $v > 0 ? '▲' : '▼' }} {{ F::persen(abs($v), $baikNaik === false ? 0 : 1) }}</span> {{ $teks }}
                    @endif
                    {{ $catatan }}
                </div>
            </div>
        @endforeach
    </div>
</section>
