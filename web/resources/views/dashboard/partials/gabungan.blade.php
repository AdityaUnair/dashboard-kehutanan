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

<section class="bagian" id="tren-gabungan">
    <div class="bagian-kepala">
        <span class="no">G1</span><h2>Tren gabungan: semua data dalam satu skala</h2>
        <span class="label-sumber">D2 + D3 + D4 + D5 · indeks</span>
    </div>
    <p class="sub">Satuan tiap data berbeda (ha, ha/tahun, m³), jadi tidak boleh dijumlahkan. Supaya bisa dibandingkan, setiap deret
        diubah menjadi <strong>indeks 2018 = 100</strong>: nilai tahun itu ÷ nilai 2018 × 100.</p>

    <div class="kartu">
        <h3>Indeks 2018 = 100</h3>
        <div class="ket">Deforestasi memakai tahun akhir periode (mis. 2018–2019 → 2019). Garis berhenti/terputus = data tidak tersedia (tutupan 2020 dibuang, lihat bagian 2). Sumbu Y skala log.</div>
        <div class="chart sedang"><canvas id="chartIndeks"></canvas></div>
        <div class="insight">
            <p><b class="tag">Terlihat</b>
                Tutupan hutan dan produksi kayu bergerak naik pelan, sementara karhutla melonjak tajam pada 2019 dan 2023 dan deforestasi
                anjlok setelah 2019. Karhutla adalah deret paling bergejolak.</p>
            <p><b class="tag">Kenapa</b>
                Karhutla dipicu cuaca (El Niño) sehingga naik-turun drastis, sedangkan produksi kayu dan tutupan hutan dipengaruhi
                faktor jangka panjang seperti izin, penanaman, dan permintaan industri.</p>
        </div>
    </div>
</section>

<section class="bagian" id="kawasan-vs-tutupan">
    <div class="bagian-kepala">
        <span class="no">G2</span><h2>Seharusnya vs kenyataan</h2>
        <span class="label-sumber">D1 + D2 · {{ $kawasanVsTutupan['tahun'] }} · ha</span>
    </div>
    <p class="sub">Luas kawasan hutan (status hukum) dibandingkan dengan luas tutupan hutan (citra satelit) di provinsi yang sama.</p>

    <div class="grid-2">
        <div class="kartu">
            <h3>Kawasan hutan vs tutupan hutan, 15 provinsi dengan kawasan terluas</h3>
            <div class="ket">Tutupan hutan bisa berada di dalam maupun di luar kawasan</div>
            <div class="chart sedang" style="height:440px"><canvas id="chartKawasanVsTutupan"></canvas></div>
        </div>
        <div class="kartu">
            <h3>Rasio tutupan hutan ÷ luas kawasan terendah</h3>
            <div class="ket">Provinsi dengan kawasan &gt; 500 rb ha</div>
            <div class="tabel-wrap">
                <table>
                    <thead><tr><th>Provinsi</th><th class="angka">Kawasan</th><th class="angka">Tutupan hutan</th><th class="angka">Rasio</th></tr></thead>
                    <tbody>
                    @foreach ($kawasanVsTutupan['rasio_rendah'] as $r)
                        <tr>
                            <td>{{ $r['nama'] }}</td>
                            <td class="angka">{{ F::ringkas($r['kawasan']) }}</td>
                            <td class="angka">{{ F::ringkas($r['hutan']) }}</td>
                            <td class="angka"><strong>{{ F::persen($r['rasio'], 0) }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="insight">
                <p><b class="tag">Terlihat</b>
                    Secara nasional luas tutupan hutan hanya {{ F::persen($kawasanVsTutupan['rasio_nasional'], 0) }} dari luas kawasan hutan.
                    Di provinsi pada tabel, jauh lebih rendah.</p>
                <p><b class="tag">Kenapa</b>
                    Banyak kawasan hutan sudah berubah menjadi semak, kebun, atau lahan terbuka namun statusnya belum diubah.
                    Rasio rendah = tanda kawasan yang sudah terdegradasi.</p>
            </div>
        </div>
    </div>
</section>

<section class="bagian" id="benang-merah">
    <div class="bagian-kepala">
        <span class="no">G3</span><h2>Benang merah per provinsi</h2>
        <span class="label-sumber">D3 + D4 + D5 · {{ $benangMerah['periode'] }}</span>
    </div>
    <p class="sub">Setiap titik = satu provinsi. Sumbu X = total luas karhutla, sumbu Y = total deforestasi netto, ukuran titik = total produksi kayu.
        Periode diseragamkan ke {{ $benangMerah['periode'] }} (irisan ketiga data).</p>

    <div class="kartu">
        <div class="chart" style="height:460px"><canvas id="chartBenangMerah"></canvas></div>
        <div class="angka-besar">
            <div><span>Korelasi karhutla ↔ deforestasi</span><strong>r = {{ F::angka($rKarhutla, 2) }}</strong> <span>hubungan {{ $kekuatan($rKarhutla) }}</span></div>
            <div><span>Korelasi produksi kayu ↔ deforestasi</span><strong>r = {{ F::angka($rKayu, 2) }}</strong> <span>hubungan {{ $kekuatan($rKayu) }}</span></div>
            <div><span>Jumlah provinsi</span><strong>{{ $benangMerah['titik']->count() }}</strong> <span>Kaltara digabung ke Kaltim</span></div>
        </div>
        <div class="insight">
            @if ($rKayu > $rKarhutla)
                <p><b class="tag">Terlihat</b>
                    Di tingkat provinsi, produksi kayu justru lebih erat hubungannya dengan deforestasi (r = {{ F::angka($rKayu, 2) }}, {{ $kekuatan($rKayu) }})
                    dibanding karhutla (r = {{ F::angka($rKarhutla, 2) }}, {{ $kekuatan($rKarhutla) }}). Riau dan Kalimantan Timur, produsen kayu
                    besar, juga paling banyak kehilangan hutan. Sebaliknya Nusa Tenggara Timur punya karhutla luas tetapi deforestasinya kecil.</p>
                <p><b class="tag">Kenapa</b>
                    Provinsi produsen kayu adalah provinsi dengan konsesi hutan tanaman dan perkebunan terluas; pembukaan hutan alam untuk
                    ditanami ulang tercatat sebagai deforestasi. Kebakaran di NTT banyak terjadi di savana dan padang rumput yang memang
                    bukan hutan, sehingga tidak menambah deforestasi. Ingat: korelasi bukan sebab-akibat, dan hanya ada
                    {{ $benangMerah['titik']->count() }} titik.</p>
            @else
                <p><b class="tag">Terlihat</b>
                    Provinsi dengan karhutla luas cenderung juga mengalami deforestasi besar (r = {{ F::angka($rKarhutla, 2) }}),
                    lebih erat dibanding produksi kayu (r = {{ F::angka($rKayu, 2) }}).</p>
                <p><b class="tag">Kenapa</b>
                    Kebakaran di lahan gambut langsung menghilangkan tutupan hutan, sedangkan kayu banyak berasal dari hutan tanaman
                    yang dipanen dan ditanam ulang. Korelasi bukan sebab-akibat; jumlah provinsi juga sedikit.</p>
            @endif
        </div>
    </div>
</section>

<section class="bagian" id="validasi">
    <div class="bagian-kepala">
        <span class="no">G4</span><h2>Validasi silang: apakah dua sumber sepakat?</h2>
        <span class="label-sumber">D2 vs D5 · 2018–2022</span>
    </div>
    <p class="sub">Kalau hutan hilang karena deforestasi, maka tutupan hutan seharusnya turun sebesar total deforestasi netto pada periode yang sama.</p>

    <div class="kartu">
        <div class="angka-besar">
            <div><span>Perubahan tutupan hutan 2018 → 2022 (D2)</span><strong>{{ F::ringkas($validasi['perubahan_tutupan']) }} ha</strong></div>
            <div><span>Perkiraan dari deforestasi netto 2018–2022 (D5)</span><strong>{{ F::ringkas($validasi['perkiraan_dari_deforestasi']) }} ha</strong></div>
            <div><span>Selisih</span><strong>{{ F::ringkas($validasi['selisih']) }} ha</strong></div>
        </div>
        <div class="insight peringatan">
            <p><b class="tag">Temuan</b>
                Kedua sumber <strong>tidak sepakat</strong>: menurut D5 hutan berkurang, sedangkan menurut D2 hutan bertambah.</p>
            <p><b class="tag">Kenapa</b>
                D2 adalah hasil rekalkulasi 2023 (seluruh tahun dihitung ulang dengan peta terbaru), sedangkan D5 disusun dari buku
                deforestasi per periode yang terbit lebih dulu. Definisi hutan tanaman dan reforestasi juga berbeda. Integrasi data
                membuat ketidakcocokan ini terlihat; tanpa digabung, tiap data tampak benar sendiri-sendiri.</p>
        </div>
    </div>
</section>
