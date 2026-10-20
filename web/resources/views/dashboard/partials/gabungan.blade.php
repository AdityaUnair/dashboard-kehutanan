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
    {{-- G2: dari mana kenaikan tutupan hutan --}}
    @php [$g2Total, $g2Sekunder, $g2Tanaman, $g2Primer, $g2Defor] = array_column($neraca['komponen'], 'nilai'); @endphp
    <x-grafik id="chartNeracaHutan" jangkar="neraca-hutan" kode="G2" ikon="lapisan" class="xl:col-span-2" tinggi="h-[300px]"
              judul="Hutan bertambah dari mana? Perubahan {{ str_replace('–', ' → ', $neraca['periode']) }}"
              ket="D1 + D2 + D5 · ha · nasional · {{ $neraca['periode'] }} (irisan ketiga data) · hijau = bertambah, merah karat = berkurang">
        <x-slot:ringkas>
            Kenaikan tutupan hutan {{ F::ringkas($g2Total) }} ha hampir seluruhnya berasal dari hutan sekunder dan hutan tanaman; hutan primer nyaris tetap
            ({{ F::ringkas($g2Primer) }} ha), padahal D5 mencatat {{ F::ringkas(abs($g2Defor)) }} ha hutan hilang.
        </x-slot:ringkas>
    </x-grafik>

    <div class="kartu flex flex-col">
        <div class="kartu-judul"><span class="mr-1 text-ink-3">G2</span>Rincian perubahan, {{ $neraca['periode'] }}</div>
        <div class="kartu-ket">Angka asli dari database (ha)</div>
        <table class="tabel-data">
            <thead><tr><th>Komponen</th><th>Data</th><th class="angka">Perubahan (ha)</th></tr></thead>
            <tbody>
            @foreach ($neraca['komponen'] as $k)
                <tr>
                    <td class="text-ink">{{ $k['label'] }}</td>
                    <td>{{ $k['kode'] }}</td>
                    <td class="angka font-semibold text-ink">{{ $k['nilai'] > 0 ? '+' : '' }}{{ F::angka($k['nilai'], 1) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <x-insight class="mt-auto border-0 bg-surface-2">
            <x-slot:terlihat>
                Hutan sekunder (+{{ F::ringkas($g2Sekunder) }}) dan hutan tanaman (+{{ F::ringkas($g2Tanaman) }}) menyumbang hampir seluruh kenaikan total.
                Hilangnya hutan menurut D5 tidak tampak sebagai turunnya hutan primer di D1.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Deforestasi netto sudah dikurangi hutan yang tumbuh kembali, jadi seharusnya searah dengan perubahan total. Bahwa D2 dan D1 justru naik
                memperkuat dugaan di G4: dua kelompok buku KLHK ini dihitung dengan cara berbeda. Ini kesimpulan kami dari penggabungan data, bukan
                pernyataan BPS/KLHK.
            </x-slot:kenapa>
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

    {{-- G3 angka korelasi + G4 validasi silang --}}
    <div class="grid content-start gap-3">
        <div class="grid grid-cols-3 gap-2">
            @foreach ([['r karhutla ↔ defor.', F::angka($rKarhutla, 2), 'hubungan '.$kekuatan($rKarhutla)], ['r kayu ↔ defor.', F::angka($rKayu, 2), 'hubungan '.$kekuatan($rKayu)], ['Provinsi (titik)', $benangMerah['titik']->count(), 'di grafik G3']] as [$label, $nilai, $arti])
                <div class="kartu p-3">
                    <div class="text-[11px] leading-tight text-ink-3">{{ $label }}</div>
                    <div class="mt-1 text-xl font-bold">{{ $nilai }}</div>
                    <div class="text-[11px] text-ink-3">{{ $arti }}</div>
                </div>
            @endforeach
        </div>

        <div id="validasi" class="kartu scroll-mt-28 border-peringatan/30 bg-peringatan-lembut">
            <div class="kartu-judul"><span class="mr-1 text-ink-3">G4</span>Validasi silang: apakah D2 dan D5 sepakat?</div>
            <div class="kartu-ket">2018–2022 · kalau hutan hilang (D5), luas hutan di D2 seharusnya ikut berkurang</div>
            <dl class="grid gap-1.5 text-[12.5px]">
                @foreach ([
                    ['D2: perubahan luas hutan 2018 → 2022', $validasi['perubahan_tutupan']],
                    ['D5: hutan hilang 2018–2022 (perkiraan perubahan)', $validasi['perkiraan_dari_deforestasi']],
                    ['Selisih kedua data', $validasi['selisih']],
                ] as [$label, $nilai])
                    <div class="flex items-baseline justify-between gap-3 border-b border-peringatan/15 pb-1.5 last:border-0">
                        <dt class="text-ink-2">{{ $label }}</dt>
                        <dd class="font-semibold whitespace-nowrap text-ink tabular-nums">{{ $nilai > 0 ? '+' : '' }}{{ F::ringkas($nilai) }} ha</dd>
                    </div>
                @endforeach
            </dl>
            <x-insight :peringatan="true" class="mt-3 border-0 bg-transparent p-0">
                <p><b>Temuan</b><span>Dua data resmi ini <strong>tidak cocok</strong>: menurut D5 hutan berkurang sekitar
                    {{ F::ringkas(abs($validasi['perkiraan_dari_deforestasi'])) }} ha, menurut D2 justru bertambah {{ F::ringkas($validasi['perubahan_tutupan']) }} ha.</span></p>
                <x-slot:kenapa>
                    Kemungkinan karena cara menghitungnya berbeda. Tabel D2 bersumber dari buku <em>Rekalkulasi Penutupan Lahan 2023</em> (semua
                    tahun dihitung ulang), sedangkan D5 dari buku-buku deforestasi per periode yang terbit lebih dulu. Perbedaan ini baru kelihatan
                    setelah kedua data digabung; kalau dibaca terpisah, masing-masing tampak wajar. G2 memperlihatkan dari mana kenaikan D2 berasal
                (hutan sekunder dan hutan tanaman, menurut D1).
                </x-slot:kenapa>
            </x-insight>
        </div>
    </div>
</div>
