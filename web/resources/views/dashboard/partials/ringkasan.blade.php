@use('App\Support\Format', 'F')

@php
    // [kunci deret, ikon, label, nilai, satuan, keterangan, [perubahan, teks, naik-itu-baik?], kode, kelas warna ikon]
    $tiles = [
        ['tutupan', 'daun', 'Tutupan hutan '.$kpi['tutupan']['tahun'], F::ringkas($kpi['tutupan']['nilai']), 'ha',
            F::persen($kpi['tutupan']['persen']).' daratan', [$kpi['tutupan']['perubahan'], 'vs 2018', true], 'D2', 'bg-brand-lembut text-brand-teks'],
        ['deforestasi', 'kapak', 'Deforestasi netto '.str_replace('-', '–', $kpi['deforestasi']['periode']), F::ringkas($kpi['deforestasi']['nilai']), 'ha/th',
            null, [$kpi['deforestasi']['perubahan'], 'vs 2018–2019', false], 'D5', 'bg-[#c58a14]/12 text-[#9a6a0c]'],
        ['karhutla', 'api', 'Kebakaran hutan & lahan '.$kpi['karhutla']['tahun'], F::ringkas($kpi['karhutla']['nilai']), 'ha',
            $kpi['karhutla']['tahun_penuh'].' (setahun penuh): '.F::ringkas($kpi['karhutla']['nilai_penuh']).' · puncak '.$kpi['karhutla']['puncak_tahun'].': '.F::ringkas($kpi['karhutla']['puncak']), null, 'D3', 'bg-peringatan-lembut text-peringatan'],
        ['kayu', 'kayu', 'Produksi kayu bulat '.$kpi['kayu']['tahun'], F::ringkas($kpi['kayu']['nilai']), 'm³',
            null, [$kpi['kayu']['perubahan'], 'vs 2018', null], 'D4', 'bg-kayu-lembut text-kayu'],
        ['kawasan', 'perisai', 'Luas kawasan hutan '.$kpi['kawasan']['tahun'], F::ringkas($kpi['kawasan']['nilai']), 'ha',
            'status hukum, nyaris tetap', null, 'D1', 'bg-brand-lembut text-brand-teks'],
    ];
    $temuan = [
        ['karhutla', 'api', 'Karhutla meledak di tahun El Niño',
            'Luas terbakar '.$karhutla['puncak'].' mencapai '.F::ringkas($karhutla['nasional'][$karhutla['puncak']]).' ha dan '.$karhutla['puncak_kedua'].' '.F::ringkas($karhutla['nasional'][$karhutla['puncak_kedua']]).' ha, beberapa kali lipat tahun lain.'],
        ['deforestasi', 'kapak', 'Laju deforestasi turun tajam sejak 2019',
            'Dari '.F::ringkas($kpi['deret']['deforestasi']['2018-2019'] ?? null).' ha/th (2018–2019) menjadi '.F::ringkas($kpi['deforestasi']['nilai']).' ha/th ('.str_replace('-', '–', $kpi['deforestasi']['periode']).').'],
        ['benang-merah', 'kayu', 'Produksi kayu paling dekat dengan deforestasi',
            'Antarprovinsi, kayu ↔ deforestasi r = '.F::angka($benangMerah['r_kayu_defor'], 2).', lebih kuat dari karhutla ↔ deforestasi (r = '.F::angka($benangMerah['r_karhutla_defor'], 2).').'],
        ['validasi', 'lapisan', 'Dua sumber resmi tidak sepakat',
            'Tutupan hutan (D2) naik '.F::ringkas($validasi['perubahan_tutupan']).' ha, padahal deforestasi netto (D5) menunjukkan hutan berkurang.'],
    ];
    $panelIndeks = [
        ['Tutupan hutan (D2)', 'tutupan', 'daun'], ['Karhutla (D3)', 'karhutla', 'api'],
        ['Produksi kayu (D4)', 'kayu', 'kayu'], ['Deforestasi netto (D5)', 'deforestasi', 'kapak'],
    ];
@endphp

{{-- Banner dengan ilustrasi hutan --}}
<div class="relative overflow-hidden rounded-3xl bg-[#6f9a78] px-6 pt-5 pb-16 text-[#f6f1e4] md:px-8 md:pt-6 md:pb-20">
    <div class="absolute inset-x-0 bottom-0 h-[55%]"><x-lanskap-hutan /></div>
    <div class="relative max-w-2xl">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-black/15 px-3 py-1 text-[11.5px] font-medium backdrop-blur">
            <x-ikon nama="pohon" class="size-3.5" /> Integrasi 5 sumber data · 2018–2026 (2026: Januari–Juli) · 34 provinsi
        </span>
        <h1 class="mt-3 text-balance text-[24px] leading-[1.15] font-semibold text-white drop-shadow-sm md:text-[26px]">
            Hutan Indonesia: berapa yang tersisa, dan apa yang menggerusnya?
        </h1>
        <p class="mt-2 max-w-xl text-[14px] leading-relaxed text-[#eef3e6]/90">
            Data BPS dan KLHK dalam tiga format (XLSX, CSV, JSON) diekstrak, diseragamkan, lalu dimuat ke satu database.
        </p>
    </div>
</div>

<div class="relative z-10 -mt-12 grid grid-cols-1 gap-3 px-2 sm:grid-cols-2 md:px-4 lg:grid-cols-5">
    @foreach ($tiles as [$kunci, $ikon, $label, $nilai, $satuan, $catatan, $delta, $kode, $warnaIkon])
        <div class="kartu flex flex-col p-4 shadow-[0_8px_24px_-12px_rgba(40,30,10,0.25)]">
            <div class="flex items-start justify-between gap-2">
                <span class="grid size-9 place-items-center rounded-xl {{ $warnaIkon }}"><x-ikon :nama="$ikon" class="size-[18px]" /></span>
                <span class="text-[10.5px] font-semibold text-ink-3">{{ $kode }}</span>
            </div>
            <div class="mt-2 text-[12.5px] text-ink-2">{{ $label }}</div>
            <div class="mt-1 text-[26px] leading-none font-semibold">
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
            <div class="mt-2 h-9"><canvas id="spark-{{ $kunci }}" aria-hidden="true"></canvas></div>
        </div>
    @endforeach
</div>

<div class="mt-3 grid gap-3 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
    <div class="kartu">
        <div class="flex items-center gap-2">
            <span class="grid size-8 place-items-center rounded-lg bg-brand-lembut text-brand-teks"><x-ikon nama="grafik" class="size-4" /></span>
            <div class="kartu-judul">Tren gabungan, indeks 2018 = 100</div>
        </div>
        <div class="kartu-ket mt-2">Satuan tiap data berbeda, jadi semuanya diubah ke indeks (nilai tahun itu ÷ nilai 2018 × 100). Tiap panel memakai skala sendiri (lihat angka sumbu);
            garis tipis = 100 (sama dengan 2018), garis putus-putus = data tidak tersedia.</div>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($panelIndeks as [$judul, $kunci, $ikon])
                <div class="rounded-xl border border-garis bg-surface-2 p-3">
                    <div class="flex items-center gap-1.5 text-[12.5px] font-medium text-ink-2"><x-ikon :nama="$ikon" class="size-3.5" /> {{ $judul }}</div>
                    <div class="mt-1 h-28"><canvas id="indeks-{{ $kunci }}" aria-label="Indeks {{ $judul }}"></canvas></div>
                </div>
            @endforeach
        </div>
        <details class="lihat-tabel" data-tabel-indeks>
            <summary>Lihat tabel data</summary>
            <div class="isi"></div>
        </details>
    </div>

    <div class="kartu">
        <div class="kartu-judul">Temuan utama</div>
        <div class="kartu-ket">Klik untuk membuka analisis lengkapnya</div>
        <ol class="grid gap-2">
            @foreach ($temuan as [$tab, $ikon, $judul, $isi])
                <li>
                    <a href="#{{ $tab }}" class="group flex gap-3 rounded-xl border border-garis bg-surface-2 p-3 transition hover:border-brand/40 hover:bg-sorot">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-surface text-brand-teks shadow-[inset_0_0_0_1px_var(--garis)]"><x-ikon :nama="$ikon" class="size-4" /></span>
                        <span class="min-w-0">
                            <span class="block text-[13.5px] font-medium text-ink">{{ $judul }}</span>
                            <span class="mt-0.5 block text-[12.5px] leading-relaxed text-ink-2">{{ $isi }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
</div>
