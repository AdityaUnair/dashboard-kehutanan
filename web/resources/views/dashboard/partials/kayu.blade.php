@use('App\Support\Format', 'F')

<section class="bagian" id="kayu">
    <div class="bagian-kepala">
        <span class="no">5</span><h2>Penggerus 2: produksi kayu bulat</h2>
    </div>

    <div class="grid-2">
        <div class="kartu">
            <h3>Produksi kayu bulat nasional per tahun</h3>
            <div class="ket">2018–{{ $kayu['tahun']->last() }}</div>
            <div class="chart sedang"><canvas id="chartKayuTren"></canvas></div>
        </div>
        <div class="kartu">
            <div class="filter">
                <label for="pilihTahunKayu">Tahun</label>
                <select id="pilihTahunKayu">
                    @foreach ($kayu['tahun'] as $t)
                        <option value="{{ $t }}" @selected($loop->last)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <h3>10 provinsi produsen kayu terbesar</h3>
            <div class="ket">m³</div>
            <div class="chart sedang" style="height:320px"><canvas id="chartKayuProvinsi"></canvas></div>
        </div>
    </div>

    <div class="insight">
        <p><b class="tag">Terlihat</b>
            Produksi naik terus hingga puncaknya pada {{ $kayu['puncak'] }} ({{ F::ringkas($kayu['nasional'][$kayu['puncak']]) }} m³), lalu turun.
            Pada {{ $kayu['tahun']->last() }}, {{ $kayu['teratas'] }} sendiri menghasilkan {{ F::persen($kayu['teratas_persen'], 0) }} kayu bulat nasional,
            dan tiga provinsi teratas {{ F::persen($kayu['top3_persen'], 0) }}.</p>
        <p><b class="tag">Kenapa</b>
            Sebagian besar kayu bulat berasal dari hutan tanaman industri (HTI) untuk bahan baku pulp dan kertas, terutama di Riau,
            Sumatera Selatan, dan Kalimantan. Jadi produksi tinggi <em>tidak otomatis</em> berarti hutan alam rusak; yang ditebang
            banyak berupa tanaman yang memang ditanam untuk dipanen.</p>
        <p><b class="tag">Lanjut</b>
            Benarkah begitu? Bagian gabungan (G3) menguji apakah provinsi yang banyak terbakar atau banyak memproduksi kayu juga paling banyak kehilangan hutan.</p>
    </div>
</section>
