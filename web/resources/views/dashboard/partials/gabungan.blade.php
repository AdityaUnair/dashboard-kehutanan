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
    {{-- G2: validasi silang D5 -> D2 -> D1, dibaca berurutan mengikuti nomor --}}
    @php
        $v = $validasi;
        $langkah = [
            ['1', 'Harapan dari D5', 'Deforestasi netto mencatat <b>'.F::ringkas($v['hilang_d5']).' ha</b> hutan hilang selama '.$v['periode'].'. Kalau benar, tutupan hutan mestinya turun kira-kira sebanyak itu.'],
            ['2', 'Kenyataan di D2', 'Tutupan hutan justru <b>naik '.F::ringkas($v['total_d2']).' ha</b>. Selisih kedua data '.F::ringkas($v['selisih']).' ha, jadi keduanya <b>tidak cocok</b>.'],
            ['3', 'Petunjuk dari D1', 'Kenaikan itu datang dari hutan sekunder (+'.F::ringkas($v['jenis_d1']['sekunder']).') dan hutan tanaman (+'.F::ringkas($v['jenis_d1']['tanaman']).'). Hutan primer nyaris tetap ('.F::ringkas($v['jenis_d1']['primer']).'), jadi hutan yang hilang menurut D5 tidak tampak sebagai turunnya hutan primer.'],
        ];
    @endphp
    <x-grafik id="chartValidasi" jangkar="validasi" kode="G2" ikon="lapisan" class="xl:col-span-2" tinggi="h-[350px]"
              judul="Validasi silang: hutan berkurang atau bertambah, {{ str_replace('–', ' → ', $v['periode']) }}?"
              ket="D1 + D2 + D5 · ha · nasional · {{ $v['periode'] }} (irisan ketiga data) · baca berurutan 1 → 3 bersama panel kanan">
        <x-slot:ringkas>
            D5 bilang hutan berkurang {{ F::ringkas($v['hilang_d5']) }} ha, D2 bilang bertambah {{ F::ringkas($v['total_d2']) }} ha. D1 menunjukkan tambahan itu
            berasal dari hutan sekunder dan hutan tanaman, bukan hutan primer.
        </x-slot:ringkas>
    </x-grafik>

    <div class="kartu border-peringatan/30 bg-peringatan-lembut">
        <div class="kartu-judul"><span class="mr-1 text-ink-3">G2</span>Cara membaca: apakah D2 dan D5 sepakat?</div>
        <ol class="mt-3 grid gap-3 text-[12.5px] leading-relaxed text-ink-2">
            @foreach ($langkah as [$no, $judulLangkah, $isi])
                <li class="flex gap-2.5">
                    <span class="grid size-6 shrink-0 place-items-center rounded-full bg-surface text-[12px] font-bold text-peringatan shadow-[inset_0_0_0_1px_var(--garis)]">{{ $no }}</span>
                    <span><b class="block font-semibold text-ink">{{ $judulLangkah }}</b>{!! $isi !!}</span>
                </li>
            @endforeach
        </ol>
        <div class="mt-3 rounded-xl bg-surface/70 p-3 text-[12.5px] leading-relaxed text-ink-2">
            <b class="block font-semibold text-ink">Kesimpulan (dugaan kami)</b>
            Kemungkinan kedua kelompok data dihitung dengan cara berbeda: D1 dan D2 dari buku <em>Rekalkulasi Penutupan Lahan</em> (semua tahun
            dihitung ulang), D5 dari buku deforestasi per periode yang terbit lebih dulu. Ini kesimpulan kami dari penggabungan data, bukan
            pernyataan BPS/KLHK. Perbedaan seperti ini baru terlihat setelah data digabung; dibaca terpisah, masing-masing tampak wajar.
        </div>
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
