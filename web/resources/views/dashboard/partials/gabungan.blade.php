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
    {{-- G2: seharusnya vs kenyataan --}}
    <x-grafik id="chartKawasanVsTutupan" jangkar="kawasan-vs-tutupan" kode="G2" ikon="lapisan" class="xl:col-span-2"
              judul="Seharusnya vs kenyataan: kawasan hutan vs tutupan hutan" tinggi="h-[340px]"
              ket="D1 + D2 · {{ $kawasanVsTutupan['tahun'] }} · ha · 15 provinsi dengan kawasan terluas · tutupan bisa di dalam maupun di luar kawasan" />

    <div class="kartu flex flex-col">
        <div class="kartu-judul"><span class="mr-1 text-ink-3">G2</span>Rasio tutupan ÷ kawasan terendah</div>
        <div class="kartu-ket">Provinsi dengan kawasan lebih dari 500 rb ha</div>
        <table class="tabel-data">
            <thead><tr><th>Provinsi</th><th class="angka">Kawasan (ha)</th><th class="angka">Tutupan (ha)</th><th class="angka">Rasio</th></tr></thead>
            <tbody>
            @foreach ($kawasanVsTutupan['rasio_rendah'] as $r)
                <tr>
                    <td class="text-ink">{{ $r['nama'] }}</td>
                    <td class="angka">{{ F::angka($r['kawasan']) }}</td>
                    <td class="angka">{{ F::angka($r['hutan']) }}</td>
                    <td class="angka font-semibold text-ink">{{ F::persen($r['rasio'], 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <x-insight class="mt-auto border-0 bg-surface-2">
            <x-slot:terlihat>
                Secara nasional luas tutupan hutan hanya {{ F::persen($kawasanVsTutupan['rasio_nasional'], 0) }} dari luas kawasan hutan; di provinsi
                pada tabel jauh lebih rendah.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Banyak kawasan hutan sudah berubah menjadi semak, kebun, atau lahan terbuka, tetapi statusnya belum diubah. Rasio rendah = tanda
                kawasan yang sudah terdegradasi.
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
                    setelah kedua data digabung; kalau dibaca terpisah, masing-masing tampak wajar.
                </x-slot:kenapa>
            </x-insight>
        </div>
    </div>
</div>
