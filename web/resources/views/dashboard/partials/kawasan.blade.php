@use('App\Support\Format', 'F')

@php
    $perubahanKawasan = ($kawasan['akhir']['total'] - $kawasan['awal']['total']) / $kawasan['awal']['total'] * 100;
@endphp

<x-bagian id="kawasan" nomor="1" judul="Seharusnya: luas kawasan hutan" sumber="D1 · BPS · XLSX · ha"
          sub="Kawasan hutan adalah wilayah yang ditetapkan pemerintah sebagai hutan lewat SK Menteri — status hukum, bukan hasil pengamatan lapangan.">
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
        <x-grafik id="chartKawasanProvinsi" judul="Luas kawasan hutan per provinsi, {{ $kawasan['tahun'] }}"
                  ket="Dipecah menurut fungsi. Kalimantan Timur masih termasuk Kalimantan Utara." tinggi="h-[600px]" />

        <div class="grid content-start gap-4">
            <x-grafik id="chartKawasanFungsi" judul="Luas menurut fungsi kawasan, {{ $kawasan['tahun'] }}"
                      ket="Total nasional {{ F::ringkas(array_sum($kawasan['fungsi'])) }} ha" tinggi="h-56" />

            <div class="kartu">
                <div class="kartu-judul">Total nasional nyaris tidak berubah</div>
                <div class="kartu-ket">Cukup satu angka, bukan grafik: perubahannya terlalu kecil untuk digambar</div>
                <div class="flex items-end gap-6">
                    <div>
                        <div class="text-xs text-ink-3">{{ $kawasan['awal']['tahun'] }}</div>
                        <div class="text-2xl font-bold tracking-tight">{{ F::ringkas($kawasan['awal']['total']) }} <span class="text-sm font-medium text-ink-3">ha</span></div>
                    </div>
                    <div class="pb-1 text-ink-3">→</div>
                    <div>
                        <div class="text-xs text-ink-3">{{ $kawasan['akhir']['tahun'] }}</div>
                        <div class="text-2xl font-bold tracking-tight">{{ F::ringkas($kawasan['akhir']['total']) }} <span class="text-sm font-medium text-ink-3">ha</span></div>
                    </div>
                    <div class="ml-auto pb-1 text-sm font-semibold text-ink-2">{{ F::persen($perubahanKawasan, 2, true) }}</div>
                </div>
            </div>
        </div>
    </div>

    <x-insight>
        <x-slot:terlihat>
            Tiga provinsi ({{ $kawasan['top3']->join(', ', ' dan ') }}) memegang {{ F::persen($kawasan['top3_persen']) }} kawasan hutan nasional;
            Papua dan Papua Barat saja {{ F::persen($kawasan['papua_persen']) }}. Sekitar {{ F::persen($kawasan['produksi_persen'], 0) }} kawasan
            berfungsi <em>produksi</em> (boleh ditebang dengan izin).
        </x-slot:terlihat>
        <x-slot:kenapa>
            Luas kawasan hanya berubah bila ada SK penetapan baru (mis. Bengkulu dan Kalimantan Selatan memperbarui SK pada 2023). Karena itu angka
            ini menggambarkan <em>rencana</em>, bukan kondisi hutan sebenarnya.
        </x-slot:kenapa>
        <x-slot:lanjut>Apakah kawasan itu memang masih berhutan? Bagian 2 melihat tutupan hutan dari citra satelit.</x-slot:lanjut>
    </x-insight>
</x-bagian>
