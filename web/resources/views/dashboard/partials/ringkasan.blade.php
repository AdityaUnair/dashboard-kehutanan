@use('App\Support\Format', 'F')

@php
    // [kunci deret, label, nilai, satuan, keterangan, [perubahan, teks, naik-itu-baik?], kode]
    $tiles = [
        ['tutupan', 'Tutupan hutan '.$kpi['tutupan']['tahun'], F::ringkas($kpi['tutupan']['nilai']), 'ha',
            F::persen($kpi['tutupan']['persen']).' daratan', [$kpi['tutupan']['perubahan'], 'vs 2018', true], 'D2'],
        ['deforestasi', 'Deforestasi netto '.$kpi['deforestasi']['periode'], F::ringkas($kpi['deforestasi']['nilai']), 'ha/th',
            null, [$kpi['deforestasi']['perubahan'], 'vs 2018–19', false], 'D5'],
        ['karhutla', 'Kebakaran hutan & lahan '.$kpi['karhutla']['tahun'], F::ringkas($kpi['karhutla']['nilai']), 'ha',
            'puncak '.$kpi['karhutla']['puncak_tahun'].': '.F::ringkas($kpi['karhutla']['puncak']), null, 'D3'],
        ['kayu', 'Produksi kayu bulat '.$kpi['kayu']['tahun'], F::ringkas($kpi['kayu']['nilai']), 'm³',
            null, [$kpi['kayu']['perubahan'], 'vs 2018', null], 'D4'],
        ['kawasan', 'Luas kawasan hutan '.$kpi['kawasan']['tahun'], F::ringkas($kpi['kawasan']['nilai']), 'ha',
            'status hukum, nyaris tetap', null, 'D1'],
    ];
    $rKayu = $benangMerah['r_kayu_defor'];
    $temuan = [
        ['per-data', 'D3', 'Karhutla meledak di tahun El Niño',
            'Luas terbakar '.$karhutla['puncak'].' mencapai '.F::ringkas($karhutla['nasional'][$karhutla['puncak']]).' ha dan '.$karhutla['puncak_kedua'].' '.F::ringkas($karhutla['nasional'][$karhutla['puncak_kedua']]).' ha — beberapa kali lipat tahun lain.'],
        ['per-data', 'D5', 'Laju deforestasi turun tajam sejak 2019',
            'Dari '.F::ringkas($kpi['deret']['deforestasi']['2018-2019'] ?? null).' ha/th (2018–19) menjadi '.F::ringkas($kpi['deforestasi']['nilai']).' ha/th ('.$kpi['deforestasi']['periode'].').'],
        ['gabungan', 'D3·D4·D5', 'Produksi kayu paling dekat dengan deforestasi',
            'Antarprovinsi, korelasi kayu ↔ deforestasi r = '.F::angka($rKayu, 2).', lebih kuat daripada karhutla ↔ deforestasi (r = '.F::angka($benangMerah['r_karhutla_defor'], 2).').'],
        ['gabungan', 'D2·D5', 'Dua sumber resmi tidak sepakat',
            'Tutupan hutan (D2) naik '.F::ringkas($validasi['perubahan_tutupan']).' ha, padahal deforestasi netto (D5) menunjukkan hutan berkurang.'],
    ];
@endphp

<x-judul-panel judul="Hutan Indonesia 2018–2025: berapa yang tersisa, dan apa yang menggerusnya?"
               sub="Lima data kehutanan dari BPS dan KLHK dalam tiga format (XLSX, CSV, JSON) diekstrak, diseragamkan, lalu dimuat ke satu database. Halaman ini merangkum temuan utamanya.">
    <span class="lencana">Data 2018–2025 · 34 provinsi</span>
</x-judul-panel>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
    @foreach ($tiles as [$kunci, $label, $nilai, $satuan, $catatan, $delta, $kode])
        <div class="kartu flex flex-col p-4">
            <div class="flex items-start justify-between gap-2 text-[12.5px] text-ink-2">
                <span>{{ $label }}</span><span class="text-[10.5px] font-semibold text-ink-3">{{ $kode }}</span>
            </div>
            <div class="mt-2.5 text-[28px] leading-none font-semibold tracking-tight">
                {{ $nilai }}<span class="ml-1 text-sm font-normal text-ink-3">{{ $satuan }}</span>
            </div>
            <div class="mt-2 min-h-4 text-xs text-ink-3">
                @if ($delta)
                    @php [$v, $teks, $baikNaik] = $delta; $baik = $baikNaik === null ? null : ($baikNaik ? $v > 0 : $v < 0); @endphp
                    <span @class(['inline-flex items-center rounded-md px-1.5 py-0.5 font-semibold', 'bg-brand-lembut text-brand-teks' => $baik === true,
                        'bg-peringatan-lembut text-peringatan' => $baik === false, 'bg-surface-2 text-ink-2' => $baik === null])>
                        {{ $v > 0 ? '↑' : '↓' }} {{ F::persen(abs($v), abs($v) >= 10 ? 0 : 1) }}</span>
                    {{ $teks }}
                @endif
                {{ $catatan }}
            </div>
            <div class="mt-3 h-12"><canvas id="spark-{{ $kunci }}" aria-hidden="true"></canvas></div>
        </div>
    @endforeach
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
    <x-grafik id="chartIndeks" judul="Tren gabungan, indeks 2018 = 100"
              ket="Satuan tiap data berbeda, jadi semuanya diubah ke indeks (nilai tahun itu ÷ nilai 2018 × 100) dan digambar di satu sumbu. Garis abu-abu = 100. Celah = data tidak tersedia."
              tinggi="h-80" />

    <div class="kartu">
        <div class="kartu-judul">Temuan utama</div>
        <div class="kartu-ket">Klik untuk melihat analisis lengkapnya</div>
        <ol class="grid gap-2">
            @foreach ($temuan as $i => [$tab, $kode, $judul, $isi])
                <li>
                    <a href="#{{ $tab }}" data-ke-tab="{{ $tab }}" class="group flex gap-3 rounded-xl border border-garis bg-surface-2 p-3 transition hover:border-ink-3/40">
                        <span class="grid size-6 shrink-0 place-items-center rounded-md bg-brand-lembut text-[12px] font-semibold text-brand-teks">{{ $i + 1 }}</span>
                        <span class="min-w-0">
                            <span class="flex items-center gap-2 text-[13.5px] font-medium text-ink">{{ $judul }}
                                <span class="text-[10.5px] font-normal text-ink-3">{{ $kode }}</span></span>
                            <span class="mt-0.5 block text-[12.5px] leading-relaxed text-ink-2">{{ $isi }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
</div>
