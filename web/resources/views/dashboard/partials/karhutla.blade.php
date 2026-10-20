@use('App\Support\Format', 'F')

<x-bagian id="karhutla" data-dataset="D3" hidden nomor="4" ikon="api" judul="Penggerus 1: kebakaran hutan dan lahan" sumber="D3 · SiPongi KLHK · XLSX · ha"
          sub="Luas indikatif kebakaran hutan dan lahan per provinsi. Data 2026 baru tercatat sampai Juli, jadi hanya ditampilkan di peringkat provinsi (bukan di tren).">
    <div class="grid gap-4 xl:grid-cols-2">
        <x-grafik id="chartKarhutlaTren" judul="Luas karhutla nasional per tahun"
                  ket="Dua tahun dengan kebakaran terluas diberi warna; tahun lain abu-abu. 2026* (pucat) baru berisi Januari–Juli." tinggi="h-80" />
        <x-grafik id="chartKarhutlaProvinsi" judul="10 provinsi dengan karhutla terluas" ket="ha" tinggi="h-72">
            <div class="mb-3 flex items-center gap-2">
                <label for="pilihTahunKarhutla" class="text-xs text-ink-3">Tahun</label>
                <select id="pilihTahunKarhutla" class="pilih py-1.5">
                    @foreach ($karhutla['tahun'] as $t)
                        <option value="{{ $t }}" @selected($t == $karhutla['puncak'])>{{ $karhutla['label_tahun'][$t] ?? $t }}</option>
                    @endforeach
                </select>
            </div>
        </x-grafik>
    </div>

    <x-insight>
        <x-slot:terlihat>
            Ada dua lonjakan: {{ $karhutla['puncak'] }} ({{ F::ringkas($karhutla['nasional'][$karhutla['puncak']]) }} ha) dan
            {{ $karhutla['puncak_kedua'] }} ({{ F::ringkas($karhutla['nasional'][$karhutla['puncak_kedua']]) }} ha); tahun lain jauh lebih kecil.
            Pada {{ $karhutla['puncak'] }}, {{ $karhutla['wilayah_puncak']->join(' dan ') }} menyumbang
            {{ F::persen($karhutla['wilayah_puncak_persen'], 0) }} luas terbakar.
        </x-slot:terlihat>
        <x-slot:kenapa>
            2019 dan 2023 adalah tahun El Niño: kemarau lebih panjang dan kering, sehingga lahan gambut di Kalimantan dan Sumatera mudah terbakar dan
            sulit dipadamkan. Pembukaan lahan dengan cara membakar memperparah kondisi ini.
        </x-slot:kenapa>
        <x-slot:lanjut>Kebakaran bersifat musiman. Tekanan yang lebih rutin datang dari produksi kayu (bagian 5).</x-slot:lanjut>
    </x-insight>
</x-bagian>
