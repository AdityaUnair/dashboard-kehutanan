@use('App\Support\Format', 'F')

@php
    $kekuatan = fn ($r) => match (true) {
        $r === null => 'tidak terhitung',
        abs($r) >= 0.7 => 'kuat',
        abs($r) >= 0.4 => 'sedang',
        default => 'lemah',
    };
    $rKarhutla = $benangMerah['r_karhutla_defor'];
    $rKayu = $benangMerah['r_kayu_defor'];
@endphp

<x-judul-panel judul="Analisis gabungan"
               sub="Hal-hal yang baru terlihat bila beberapa data disambungkan. Tren gabungan (indeks 2018 = 100) ada di Ringkasan." />

<div class="grid items-start gap-3 xl:grid-cols-3">
    {{-- G2: kebakaran vs hutan hilang (D3 + D5), dua garis di satu sumbu hektar --}}
    @php
        $ah = $apiHutan;
        $turunApi = ($ah['puncak']['karhutla'] - $ah['setelah']['karhutla']) / $ah['puncak']['karhutla'] * 100;
        $turunHutan = ($ah['puncak']['deforestasi'] - $ah['setelah']['deforestasi']) / $ah['puncak']['deforestasi'] * 100;
    @endphp
    <x-grafik id="chartApiHutan" jangkar="api-hutan" kode="G2" ikon="api" class="xl:col-span-2" tinggi="h-[320px]"
              judul="Apakah kebakaran besar diikuti hilangnya hutan?"
              ket="D3 + D5 · ha · nasional · {{ $ah['tahun'][0] }}–{{ end($ah['tahun']) }} (irisan kedua data) · deforestasi per periode dua tahun, digambar di tahun akhir periodenya">
        <x-slot:ringkas>
            Keduanya memuncak bersamaan pada {{ $ah['puncak']['tahun'] }}, lalu sama-sama turun tajam: karhutla rata-rata {{ F::persen($turunApi, 0) }} lebih kecil
            dan deforestasi {{ F::persen($turunHutan, 0) }} lebih kecil pada tahun-tahun sesudahnya.
        </x-slot:ringkas>
    </x-grafik>

    <div class="kartu flex flex-col">
        <div class="kartu-judul"><span class="mr-1 text-ink-3">G2</span>Kebakaran dan hutan hilang bergerak searah</div>
        <div class="mt-3 grid grid-cols-2 gap-2">
            <div class="rounded-xl bg-surface-2 p-3">
                <div class="text-[11px] text-ink-3">Puncak {{ $ah['puncak']['tahun'] }}: karhutla</div>
                <div class="mt-0.5 text-xl font-bold tabular-nums">{{ F::ringkas($ah['puncak']['karhutla']) }} <span class="text-xs font-normal text-ink-3">ha</span></div>
            </div>
            <div class="rounded-xl bg-surface-2 p-3">
                <div class="text-[11px] text-ink-3">Deforestasi {{ $ah['puncak']['tahun'] - 1 }}–{{ $ah['puncak']['tahun'] }}</div>
                <div class="mt-0.5 text-xl font-bold tabular-nums">{{ F::ringkas($ah['puncak']['deforestasi']) }} <span class="text-xs font-normal text-ink-3">ha/th</span></div>
            </div>
        </div>
        <x-insight :lipat="false" class="mt-3 border-0 bg-transparent p-0">
            <x-slot:terlihat>
                Tahun kebakaran terbesar ({{ $ah['puncak']['tahun'] }}) juga periode deforestasi tertinggi. Setelah itu kebakaran mengecil dan deforestasi
                ikut turun ke kisaran 100–120 rb ha/th. Korelasinya r = {{ F::angka($ah['r'], 2) }}.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Dugaan kami: kebakaran besar 2019 (tahun El Niño, kemarau panjang) menghabisi tutupan hutan, terutama di lahan gambut Sumatera dan
                Kalimantan. Turunnya deforestasi sesudahnya juga dipengaruhi hal lain, misalnya moratorium izin baru di hutan primer dan gambut (Inpres 5/2019).
            </x-slot:kenapa>
            <x-slot:lanjut>
                Ini pola nasional dari {{ count($ah['tahun']) }} titik waktu, bukan bukti sebab-akibat; data tidak menunjukkan lokasi yang terbakar lalu hilang hutannya.
            </x-slot:lanjut>
        </x-insight>
    </div>

    {{-- G3: benang merah --}}
    <x-grafik id="chartBenangMerah" jangkar="benang-merah" kode="G3" ikon="lapisan" class="xl:col-span-2" tinggi="h-[340px]"
              judul="Benang merah per provinsi: karhutla, deforestasi, dan produksi kayu"
              ket="D3 + D4 + D5 · {{ $benangMerah['periode'] }} · titik = provinsi, X = total karhutla, Y = total deforestasi netto, ukuran = produksi kayu, warna = pulau">
        <x-slot:ringkas>
            @if ($rKayu > $rKarhutla)
                Produksi kayu lebih erat hubungannya dengan deforestasi (r = {{ F::angka($rKayu, 2) }}, {{ $kekuatan($rKayu) }}) dibanding karhutla
                (r = {{ F::angka($rKarhutla, 2) }}, {{ $kekuatan($rKarhutla) }}). Korelasi bukan sebab-akibat.
            @else
                Karhutla lebih erat hubungannya dengan deforestasi (r = {{ F::angka($rKarhutla, 2) }}) dibanding produksi kayu (r = {{ F::angka($rKayu, 2) }}).
                Korelasi bukan sebab-akibat.
            @endif
        </x-slot:ringkas>
        <x-slot:info>
            @if ($rKayu > $rKarhutla)
                <x-insight :lipat="false">
                    <x-slot:terlihat>
                        Di tingkat provinsi, produksi kayu justru lebih erat hubungannya dengan deforestasi (r = {{ F::angka($rKayu, 2) }},
                        {{ $kekuatan($rKayu) }}) dibanding karhutla (r = {{ F::angka($rKarhutla, 2) }}, {{ $kekuatan($rKarhutla) }}). Riau dan Kalimantan
                        Timur, produsen kayu besar, juga paling banyak kehilangan hutan. Sebaliknya Nusa Tenggara Timur punya karhutla luas tetapi
                        deforestasinya kecil.
                    </x-slot:terlihat>
                    <x-slot:kenapa>
                        Provinsi produsen kayu adalah provinsi dengan konsesi hutan tanaman dan perkebunan terluas; pembukaan hutan alam untuk
                        ditanami ulang tercatat sebagai deforestasi. Kebakaran di NTT banyak terjadi di savana dan padang rumput yang memang bukan hutan.
                        Ingat: korelasi bukan sebab-akibat, dan hanya ada {{ $benangMerah['titik']->count() }} titik. Periode diseragamkan ke
                        {{ $benangMerah['periode'] }} (irisan ketiga data); Kalimantan Utara digabung ke Kalimantan Timur.
                    </x-slot:kenapa>
                </x-insight>
            @else
                <x-insight :lipat="false">
                    <x-slot:terlihat>
                        Provinsi dengan karhutla luas cenderung juga mengalami deforestasi besar (r = {{ F::angka($rKarhutla, 2) }}), lebih
                        erat dibanding produksi kayu (r = {{ F::angka($rKayu, 2) }}).
                    </x-slot:terlihat>
                    <x-slot:kenapa>
                        Kebakaran di lahan gambut langsung menghilangkan tutupan hutan, sedangkan kayu banyak berasal dari hutan tanaman yang
                        dipanen dan ditanam ulang. Korelasi bukan sebab-akibat; jumlah provinsi juga sedikit.
                    </x-slot:kenapa>
                </x-insight>
            @endif
        </x-slot:info>
    </x-grafik>

    {{-- G3: angka korelasi --}}
    <div class="grid content-start gap-3">
        @foreach ([['r karhutla ↔ deforestasi', F::angka($rKarhutla, 2), 'hubungan '.$kekuatan($rKarhutla)], ['r produksi kayu ↔ deforestasi', F::angka($rKayu, 2), 'hubungan '.$kekuatan($rKayu)], ['Provinsi (titik)', $benangMerah['titik']->count(), 'di grafik G3, '.$benangMerah['periode']]] as [$label, $nilai, $arti])
            <div class="kartu flex items-center justify-between gap-3 p-3.5">
                <div>
                    <div class="text-[12px] text-ink-2"><span class="mr-1 text-ink-3">G3</span>{{ $label }}</div>
                    <div class="text-[11px] text-ink-3">{{ $arti }}</div>
                </div>
                <div class="text-2xl font-bold tabular-nums">{{ $nilai }}</div>
            </div>
        @endforeach
        <p class="px-1 text-[11.5px] leading-relaxed text-ink-3">r = koefisien korelasi Pearson antarprovinsi: mendekati 1 = searah kuat, mendekati 0 = tidak ada hubungan.
            Korelasi bukan sebab-akibat.</p>
    </div>
</div>
