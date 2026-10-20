@use('App\Support\Format', 'F')

<section class="bagian" id="deforestasi">
    <div class="bagian-kepala">
        <span class="no">3</span><h2>Seberapa cepat hutan hilang: deforestasi netto</h2>
        <span class="label-sumber">D5 · BPS/KLHK · CSV · ha/tahun</span>
    </div>
    <p class="sub">Deforestasi netto = hutan yang hilang dikurangi hutan yang tumbuh kembali, per periode dua tahun. Negatif berarti reforestasi lebih besar.</p>

    <div class="grid-2">
        <div class="kartu">
            <h3>Deforestasi netto nasional per periode</h3>
            <div class="ket">Di dalam kawasan hutan vs di luar kawasan (APL)</div>
            <div class="chart sedang"><canvas id="chartDeforTren"></canvas></div>
        </div>
        <div class="kartu">
            <h3>10 provinsi dengan deforestasi netto terbesar</h3>
            <div class="ket">Jumlah periode 2018–2019 s.d. 2021–2022 (ha)</div>
            <div class="chart sedang"><canvas id="chartDeforProvinsi"></canvas></div>
        </div>
    </div>

    <div class="insight">
        <p><b class="tag">Terlihat</b>
            Deforestasi netto memuncak pada {{ $deforestasi['puncak'] }}, lalu turun tajam dan bertahan sekitar 100–120 rb ha/tahun.
            {{ F::persen($deforestasi['dalam_persen'], 0) }} deforestasi 2018–2022 terjadi <em>di dalam</em> kawasan hutan, dan lima provinsi
            teratas menyumbang {{ F::persen($deforestasi['top5_persen'], 0) }}.</p>
        <p><b class="tag">Kenapa</b>
            Penurunan sejak 2019–2020 bertepatan dengan moratorium permanen izin baru di hutan primer dan gambut (Inpres 5/2019)
            serta tahun-tahun basah 2020–2022. Lonjakan 2018–2019 sejalan dengan kebakaran besar 2019 (bagian 4).
            Kalimantan dan Sumatera mendominasi karena di sana ekspansi perkebunan dan hutan tanaman paling luas.</p>
        <p><b class="tag">Lanjut</b>
            Apa yang menggerus hutan? Dua tersangka utama: kebakaran (bagian 4) dan penebangan untuk kayu (bagian 5).</p>
    </div>
</section>
