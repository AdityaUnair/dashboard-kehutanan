@use('App\Support\Format', 'F')

<section class="bagian" id="kawasan">
    <div class="bagian-kepala">
        <span class="no">1</span><h2>Seharusnya: luas kawasan hutan</h2>
        <span class="label-sumber">D1 · BPS · XLSX · ha</span>
    </div>
    <p class="sub">Kawasan hutan adalah wilayah yang <em>ditetapkan</em> pemerintah sebagai hutan, bukan hasil pengamatan lapangan.</p>

    <div class="grid-2">
        <div class="kartu">
            <h3>Luas kawasan hutan per provinsi, {{ $kawasan['tahun'] }}</h3>
            <div class="ket">Dipecah menurut fungsi. Kalimantan Timur masih termasuk Kalimantan Utara.</div>
            <div class="chart tinggi"><canvas id="chartKawasanProvinsi"></canvas></div>
        </div>
        <div class="tumpuk">
            <div class="kartu">
                <h3>Komposisi fungsi kawasan nasional</h3>
                <div class="ket">Total {{ F::ringkas(array_sum($kawasan['fungsi'])) }} ha</div>
                <div class="chart"><canvas id="chartKawasanFungsi"></canvas></div>
            </div>
            <div class="kartu">
                <h3>Total nasional per tahun</h3>
                <div class="ket">Sumbu dimulai dari 125 jt ha agar perubahan kecil terlihat</div>
                <div class="chart" style="height:170px"><canvas id="chartKawasanTren"></canvas></div>
            </div>
        </div>
    </div>

    <div class="insight">
        <p><b class="tag">Terlihat</b>
            Tiga provinsi ({{ $kawasan['top3']->join(', ', ' dan ') }}) memegang {{ F::persen($kawasan['top3_persen']) }} kawasan hutan nasional;
            Papua dan Papua Barat saja {{ F::persen($kawasan['papua_persen']) }}. {{ F::persen($kawasan['produksi_persen'], 0) }} kawasan berfungsi
            <em>produksi</em> (boleh ditebang dengan izin). Luas total nyaris tidak berubah sepanjang 2018–{{ $kawasan['tahun'] }}.</p>
        <p><b class="tag">Kenapa</b>
            Luas kawasan hanya berubah bila ada SK penetapan baru (mis. Bengkulu dan Kalimantan Selatan memperbarui SK pada 2023).
            Karena itu angka ini menggambarkan <em>rencana</em>, bukan kondisi hutan sebenarnya.</p>
        <p><b class="tag">Lanjut</b>
            Apakah kawasan itu memang masih berhutan? Bagian 2 membandingkannya dengan tutupan hutan dari citra satelit.</p>
    </div>
</section>
