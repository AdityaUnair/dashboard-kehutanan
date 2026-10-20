@use('App\Support\Format', 'F')

<section class="bagian" id="karhutla">
    <div class="bagian-kepala">
        <span class="no">4</span><h2>Penggerus 1: kebakaran hutan dan lahan</h2>
        <span class="label-sumber">D3 · SiPongi KLHK · XLSX · ha</span>
    </div>
    <p class="sub">Luas indikatif kebakaran hutan dan lahan per provinsi. Data 2026 belum dipakai karena baru tercatat sampai Juli.</p>

    <div class="grid-2">
        <div class="kartu">
            <h3>Luas karhutla nasional per tahun</h3>
            <div class="ket">2018–{{ $karhutla['tahun']->last() }}</div>
            <div class="chart sedang"><canvas id="chartKarhutlaTren"></canvas></div>
        </div>
        <div class="kartu">
            <div class="filter">
                <label for="pilihTahunKarhutla">Tahun</label>
                <select id="pilihTahunKarhutla">
                    @foreach ($karhutla['tahun'] as $t)
                        <option value="{{ $t }}" @selected($t == $karhutla['puncak'])>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <h3>10 provinsi dengan karhutla terluas</h3>
            <div class="ket">Ganti tahun untuk membandingkan</div>
            <div class="chart sedang" style="height:320px"><canvas id="chartKarhutlaProvinsi"></canvas></div>
        </div>
    </div>

    <div class="insight">
        <p><b class="tag">Terlihat</b>
            Ada dua lonjakan: {{ $karhutla['puncak'] }} ({{ F::ringkas($karhutla['nasional'][$karhutla['puncak']]) }} ha) dan
            {{ $karhutla['puncak_kedua'] }} ({{ F::ringkas($karhutla['nasional'][$karhutla['puncak_kedua']]) }} ha); tahun lain jauh lebih kecil.
            Pada {{ $karhutla['puncak'] }}, {{ $karhutla['wilayah_puncak']->join(' dan ') }} menyumbang
            {{ F::persen($karhutla['wilayah_puncak_persen'], 0) }} luas terbakar.</p>
        <p><b class="tag">Kenapa</b>
            2019 dan 2023 adalah tahun El Niño: kemarau lebih panjang dan kering, sehingga lahan gambut di Kalimantan dan Sumatera
            mudah terbakar dan sulit dipadamkan. Pembukaan lahan dengan cara membakar memperparah kondisi ini.</p>
        <p><b class="tag">Lanjut</b>
            Kebakaran bersifat musiman. Tekanan yang lebih rutin datang dari produksi kayu (bagian 5).</p>
    </div>
</section>
