@use('App\Support\Format', 'F')

@php
    $perubahanKawasan = ($kawasan['akhir']['total'] - $kawasan['awal']['total']) / $kawasan['awal']['total'] * 100;
@endphp

<x-grafik id="chartKawasanFungsi" jangkar="kawasan" kode="D1" ikon="perisai" judul="Kawasan hutan menurut fungsi, {{ $kawasan['tahun'] }}"
          ket="BPS · XLSX · ha · status hukum (SK Menteri), bukan pengamatan">
    <x-slot:ringkas>
        Total nasional nyaris tetap: {{ F::ringkas($kawasan['awal']['total']) }} ha ({{ $kawasan['awal']['tahun'] }}) →
        {{ F::ringkas($kawasan['akhir']['total']) }} ha ({{ $kawasan['akhir']['tahun'] }}), <b class="font-semibold text-ink">{{ F::persen($perubahanKawasan, 2, true) }}</b>.
        Sekitar {{ F::persen($kawasan['produksi_persen'], 0) }} berfungsi produksi.
    </x-slot:ringkas>
    <x-slot:info>
        <x-insight :lipat="false">
            <x-slot:terlihat>
                Sekitar {{ F::persen($kawasan['produksi_persen'], 0) }} kawasan berfungsi <em>produksi</em> (boleh ditebang dengan izin). Total nasional
                hanya berubah {{ F::persen($perubahanKawasan, 2, true) }} antara {{ $kawasan['awal']['tahun'] }} dan {{ $kawasan['akhir']['tahun'] }}.
            </x-slot:terlihat>
            <x-slot:kenapa>
                Kawasan hutan adalah wilayah yang ditetapkan pemerintah sebagai hutan lewat SK Menteri. Luasnya hanya berubah bila ada SK penetapan baru
                (mis. Bengkulu dan Kalimantan Selatan memperbarui SK pada 2023). Karena itu angka ini menggambarkan <em>rencana</em>, bukan kondisi hutan sebenarnya.
            </x-slot:kenapa>
            <x-slot:lanjut>Apakah kawasan itu memang masih berhutan? Data D2 melihat tutupan hutan dari citra satelit.</x-slot:lanjut>
        </x-insight>
    </x-slot:info>
</x-grafik>

<x-grafik id="chartKawasanProvinsi" kode="D1" ikon="perisai" judul="10 provinsi dengan kawasan hutan terluas, {{ $kawasan['tahun'] }}"
          ket="ha, dipecah menurut fungsi · Kalimantan Timur termasuk Kalimantan Utara">
    <x-slot:ringkas>
        {{ $kawasan['top3']->join(', ', ' dan ') }} memegang {{ F::persen($kawasan['top3_persen']) }} kawasan hutan nasional;
        Papua dan Papua Barat saja {{ F::persen($kawasan['papua_persen']) }}.
    </x-slot:ringkas>
</x-grafik>
