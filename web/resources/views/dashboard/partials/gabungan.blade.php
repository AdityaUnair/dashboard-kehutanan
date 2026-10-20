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
               sub="Menyambungkan beberapa data sekaligus: hal-hal yang tidak terlihat bila tiap data dibaca sendiri-sendiri. Tren gabungan (indeks 2018 = 100) ada di halaman Ringkasan." />

<x-adegan-satelit />

<x-bagian id="kawasan-vs-tutupan" nomor="G2" judul="Seharusnya vs kenyataan" sumber="D1 + D2 · {{ $kawasanVsTutupan['tahun'] }} · ha"
          sub="Luas kawasan hutan (status hukum) dibandingkan dengan luas tutupan hutan (citra satelit) di provinsi dan tahun yang sama.">
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
        <x-grafik id="chartKawasanVsTutupan" judul="Kawasan hutan vs tutupan hutan, 15 provinsi dengan kawasan terluas"
                  ket="Tutupan hutan bisa berada di dalam maupun di luar kawasan" tinggi="h-[480px]" />
        <div class="kartu">
            <div class="kartu-judul">Rasio tutupan hutan ÷ luas kawasan terendah</div>
            <div class="kartu-ket">Provinsi dengan kawasan lebih dari 500 rb ha</div>
            <table class="tabel-data">
                <thead><tr><th>Provinsi</th><th class="angka">Kawasan</th><th class="angka">Tutupan</th><th class="angka">Rasio</th></tr></thead>
                <tbody>
                @foreach ($kawasanVsTutupan['rasio_rendah'] as $r)
                    <tr>
                        <td class="text-ink">{{ $r['nama'] }}</td>
                        <td class="angka">{{ F::ringkas($r['kawasan']) }}</td>
                        <td class="angka">{{ F::ringkas($r['hutan']) }}</td>
                        <td class="angka font-semibold text-ink">{{ F::persen($r['rasio'], 0) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <x-insight>
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
    </div>
</x-bagian>

<x-bagian id="benang-merah" nomor="G3" judul="Benang merah per provinsi" sumber="D3 + D4 + D5 · {{ $benangMerah['periode'] }}"
          sub="Setiap titik = satu provinsi. Sumbu X = total luas karhutla, sumbu Y = total deforestasi netto, ukuran titik = total produksi kayu. Periode diseragamkan ke {{ $benangMerah['periode'] }} (irisan ketiga data).">
    <x-grafik id="chartBenangMerah" judul="Karhutla, deforestasi, dan produksi kayu per provinsi" ket="Warna = pulau utama; Kalimantan Utara digabung ke Kalimantan Timur" tinggi="h-[460px]">
        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            @foreach ([['Korelasi karhutla ↔ deforestasi', $rKarhutla], ['Korelasi produksi kayu ↔ deforestasi', $rKayu]] as [$label, $r])
                <div class="rounded-xl border border-garis bg-surface-2 px-4 py-3">
                    <div class="text-xs text-ink-3">{{ $label }}</div>
                    <div class="mt-1 text-xl font-bold">r = {{ F::angka($r, 2) }} <span class="text-xs font-medium text-ink-3">hubungan {{ $kekuatan($r) }}</span></div>
                </div>
            @endforeach
            <div class="rounded-xl border border-garis bg-surface-2 px-4 py-3">
                <div class="text-xs text-ink-3">Jumlah provinsi (titik)</div>
                <div class="mt-1 text-xl font-bold">{{ $benangMerah['titik']->count() }}</div>
            </div>
        </div>
    </x-grafik>
    <x-insight>
        @if ($rKayu > $rKarhutla)
            <p><b>Terlihat</b>Di tingkat provinsi, produksi kayu justru lebih erat hubungannya dengan deforestasi (r = {{ F::angka($rKayu, 2) }},
                {{ $kekuatan($rKayu) }}) dibanding karhutla (r = {{ F::angka($rKarhutla, 2) }}, {{ $kekuatan($rKarhutla) }}). Riau dan Kalimantan
                Timur, produsen kayu besar, juga paling banyak kehilangan hutan. Sebaliknya Nusa Tenggara Timur punya karhutla luas tetapi
                deforestasinya kecil.</p>
            <p><b>Kenapa</b>Provinsi produsen kayu adalah provinsi dengan konsesi hutan tanaman dan perkebunan terluas; pembukaan hutan alam untuk
                ditanami ulang tercatat sebagai deforestasi. Kebakaran di NTT banyak terjadi di savana dan padang rumput yang memang bukan hutan.
                Ingat: korelasi bukan sebab-akibat, dan hanya ada {{ $benangMerah['titik']->count() }} titik.</p>
        @else
            <p><b>Terlihat</b>Provinsi dengan karhutla luas cenderung juga mengalami deforestasi besar (r = {{ F::angka($rKarhutla, 2) }}), lebih
                erat dibanding produksi kayu (r = {{ F::angka($rKayu, 2) }}).</p>
            <p><b>Kenapa</b>Kebakaran di lahan gambut langsung menghilangkan tutupan hutan, sedangkan kayu banyak berasal dari hutan tanaman yang
                dipanen dan ditanam ulang. Korelasi bukan sebab-akibat; jumlah provinsi juga sedikit.</p>
        @endif
    </x-insight>
</x-bagian>

<x-bagian id="validasi" nomor="G4" judul="Validasi silang: apakah dua sumber sepakat?" sumber="D2 vs D5 · 2018–2022"
          sub="Kalau hutan hilang karena deforestasi, tutupan hutan seharusnya turun kira-kira sebesar total deforestasi netto pada periode yang sama.">
    <div class="kartu">
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ([
                ['Perubahan tutupan hutan 2018 → 2022 (D2)', $validasi['perubahan_tutupan']],
                ['Perkiraan dari deforestasi netto 2018–2022 (D5)', $validasi['perkiraan_dari_deforestasi']],
                ['Selisih kedua sumber', $validasi['selisih']],
            ] as [$label, $nilai])
                <div class="rounded-xl border border-garis bg-surface-2 px-4 py-3">
                    <div class="text-xs text-ink-3">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-bold">{{ $nilai > 0 ? '+' : '' }}{{ F::ringkas($nilai) }} <span class="text-sm font-medium text-ink-3">ha</span></div>
                </div>
            @endforeach
        </div>
        <x-insight :peringatan="true">
            <p><b>Temuan</b>Kedua sumber <strong>tidak sepakat</strong>: menurut D5 hutan berkurang, sedangkan menurut D2 hutan bertambah.</p>
            <p><b>Kenapa</b>D2 adalah hasil rekalkulasi 2023 (seluruh tahun dihitung ulang dengan peta terbaru), sedangkan D5 disusun dari buku
                deforestasi per periode yang terbit lebih dulu. Definisi hutan tanaman dan reforestasi juga berbeda. Integrasi data membuat
                ketidakcocokan ini terlihat; tanpa digabung, tiap data tampak benar sendiri-sendiri.</p>
        </x-insight>
    </div>
</x-bagian>
