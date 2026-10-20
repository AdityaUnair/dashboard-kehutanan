@use('App\Support\Format', 'F')

    <div class="grid gap-4 xl:grid-cols-2">
        <x-grafik id="chartKayuTren" judul="Produksi kayu bulat nasional per tahun" ket="m³" tinggi="h-80" />
        <x-grafik id="chartKayuProvinsi" judul="10 provinsi produsen kayu terbesar" ket="m³" tinggi="h-72">
            <div class="mb-3 flex items-center gap-2">
                <label for="pilihTahunKayu" class="text-xs text-ink-3">Tahun</label>
                <select id="pilihTahunKayu" class="pilih py-1.5">
                    @foreach ($kayu['tahun'] as $t)
                        <option value="{{ $t }}" @selected($loop->last)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </x-grafik>
    </div>

    <x-insight>
        <x-slot:terlihat>
            Produksi naik terus hingga puncaknya pada {{ $kayu['puncak'] }} ({{ F::ringkas($kayu['nasional'][$kayu['puncak']]) }} m³), lalu turun.
            Pada {{ $kayu['tahun']->last() }}, {{ $kayu['teratas'] }} sendiri menghasilkan {{ F::persen($kayu['teratas_persen'], 0) }} kayu bulat nasional,
            dan tiga provinsi teratas {{ F::persen($kayu['top3_persen'], 0) }}.
        </x-slot:terlihat>
        <x-slot:kenapa>
            Sebagian besar kayu bulat berasal dari hutan tanaman industri (HTI) untuk bahan baku pulp dan kertas, terutama di Riau, Sumatera Selatan,
            dan Kalimantan. Produksi tinggi tidak otomatis berarti hutan alam rusak, karena yang ditebang banyak berupa tanaman yang memang ditanam
            untuk dipanen.
        </x-slot:kenapa>
        <x-slot:lanjut>Benarkah begitu? Bagian gabungan (G3) menguji apakah provinsi yang banyak terbakar atau banyak memproduksi kayu juga paling banyak kehilangan hutan.</x-slot:lanjut>
    </x-insight>
</x-bagian>
