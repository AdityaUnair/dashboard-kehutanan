@use('App\Support\Format', 'F')

@php
    $awal = $tutupan['nasional']->first();
    $akhir = $tutupan['nasional']->last();
@endphp

<section class="bagian" id="tutupan">
    <div class="bagian-kepala">
        <span class="no">2</span><h2>Kenyataan: tutupan hutan dari citra satelit</h2>
        <span class="label-sumber">D2 · BPS/KLHK · CSV · ribu ha → ha</span>
    </div>
    <p class="sub">Luas lahan yang benar-benar tertutup hutan (di dalam maupun di luar kawasan), hasil penafsiran citra satelit KLHK.</p>

    <div class="grid-2">
        <div class="kartu">
            <h3>Tutupan hutan nasional, 2018–{{ $tutupan['tahun_akhir'] }}</h3>
            <div class="ket">Batang = luas hutan (ha) · garis = persen daratan yang berhutan</div>
            <div class="chart sedang"><canvas id="chartTutupanTren"></canvas></div>
        </div>
        <div class="kartu">
            <h3>Perubahan tutupan hutan per provinsi, 2018 → {{ $tutupan['tahun_akhir'] }}</h3>
            <div class="ket">10 provinsi dengan perubahan paling negatif (ha)</div>
            <div class="chart sedang"><canvas id="chartTutupanProvinsi"></canvas></div>
        </div>
    </div>

    <div class="insight">
        <p><b class="tag">Terlihat</b>
            Tutupan hutan nasional naik dari {{ F::ringkas($awal['hutan']) }} ha ({{ F::persen($awal['persen']) }}) pada 2018 menjadi
            {{ F::ringkas($akhir['hutan']) }} ha ({{ F::persen($akhir['persen']) }}) pada {{ $tutupan['tahun_akhir'] }}.
            Meski begitu, {{ $tutupan['jumlah_turun'] }} dari {{ $tutupan['jumlah_provinsi'] }} provinsi tetap kehilangan hutan.</p>
        <p><b class="tag">Kenapa</b>
            Data ini hasil <em>rekalkulasi</em> KLHK 2023: semua tahun dihitung ulang dengan metode yang sama, dan hutan tanaman
            (mis. HTI akasia/eukaliptus) ikut dihitung sebagai hutan. Jadi kenaikan belum tentu berarti hutan alam pulih.
            Bisa juga karena tanaman tumbuh kembali di bekas lahan terbuka.</p>
        <p><b class="tag">Lanjut</b>
            Kalau tutupan naik, apakah hutan berhenti hilang? Bagian 3 melihat laju deforestasi.</p>
    </div>
    <div class="insight peringatan">
        <p><b class="tag">Catatan data</b>
            Tahun 2020 sengaja tidak ditampilkan. Di tabel BPS, nilai 2020 tampak tertukar antarprovinsi: delapan provinsi tercatat
            punya hutan lebih luas dari daratannya (mis. Bali 5,66 jt ha padahal luas daratan 0,57 jt ha), sehingga tahun itu dibuang
            saat transformasi. Lonjakan Jawa Tengah dan Sumatera Selatan 2018 → 2019 juga ditandai "perlu dicek" di log ETL.</p>
    </div>
</section>
