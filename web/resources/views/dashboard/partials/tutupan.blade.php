@use('App\Support\Format', 'F')

@php
    $awal = $tutupan['nasional']->first();
    $akhir = $tutupan['nasional']->last();
@endphp

<x-bagian id="tutupan" data-dataset="D2" hidden nomor="2" ikon="daun" judul="Kenyataan: tutupan hutan dari citra satelit" sumber="D2 · BPS/KLHK · CSV · ribu ha → ha"
          sub="Luas lahan yang benar-benar tertutup hutan (di dalam maupun di luar kawasan), hasil penafsiran citra satelit KLHK.">
    <div class="grid gap-4 xl:grid-cols-2">
        <x-grafik id="chartTutupanTren" judul="Tutupan hutan nasional, 2018–{{ $tutupan['tahun_akhir'] }}"
                  ket="Luas hutan (ha). Celah di 2020 = data tidak valid di sumber, sengaja tidak digambar." tinggi="h-80" />
        <x-grafik id="chartTutupanProvinsi" judul="Perubahan tutupan hutan per provinsi, 2018 → {{ $tutupan['tahun_akhir'] }}"
                  ket="8 penurunan terbesar (merah) dan 5 kenaikan terbesar (biru), ha" tinggi="h-80" />
    </div>

    <x-insight>
        <x-slot:terlihat>
            Tutupan hutan nasional naik dari {{ F::ringkas($awal['hutan']) }} ha ({{ F::persen($awal['persen']) }} daratan) pada 2018 menjadi
            {{ F::ringkas($akhir['hutan']) }} ha ({{ F::persen($akhir['persen']) }}) pada {{ $tutupan['tahun_akhir'] }}. Meski begitu,
            {{ $tutupan['jumlah_turun'] }} dari {{ $tutupan['jumlah_provinsi'] }} provinsi tetap kehilangan hutan.
        </x-slot:terlihat>
        <x-slot:kenapa>
            Data ini hasil <em>rekalkulasi</em> KLHK 2023: semua tahun dihitung ulang dengan metode yang sama, dan hutan tanaman (mis. HTI akasia
            dan eukaliptus) ikut dihitung sebagai hutan. Jadi kenaikan belum tentu berarti hutan alam pulih; bisa juga tanaman yang tumbuh di
            bekas lahan terbuka.
        </x-slot:kenapa>
        <x-slot:lanjut>Kalau tutupan naik, apakah hutan berhenti hilang? Bagian 3 melihat laju deforestasi.</x-slot:lanjut>
    </x-insight>
    <x-insight :peringatan="true">
        <p><b>Catatan data</b>Tahun 2020 tidak digambar. Di tabel BPS, nilai 2020 tampak tertukar antarprovinsi: delapan provinsi tercatat punya
            hutan lebih luas dari daratannya (mis. Bali 5,66 jt ha padahal daratannya 0,57 jt ha), sehingga tahun itu dibuang saat transformasi.
            Lonjakan Jawa Tengah dan Sumatera Selatan 2018 → 2019 juga ditandai "perlu dicek" di log ETL.</p>
    </x-insight>
</x-bagian>
