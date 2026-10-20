@use('App\Support\Format', 'F')

<x-bagian id="deforestasi" data-dataset="D5" hidden nomor="3" ikon="kapak" judul="Seberapa cepat hutan hilang: deforestasi netto" sumber="D5 · BPS/KLHK · CSV · ha/tahun"
          sub="Deforestasi netto = hutan yang hilang dikurangi hutan yang tumbuh kembali, per periode dua tahun. Nilai negatif berarti reforestasi lebih besar.">
    <div class="tata-3">
        <x-grafik id="chartDeforTren" judul="Deforestasi netto nasional per periode"
                  ket="Di dalam kawasan hutan vs di luar kawasan (APL), ha per tahun" tinggi="h-64" />
        <x-grafik id="chartDeforProvinsi" judul="10 provinsi dengan deforestasi netto terbesar"
                  ket="Jumlah periode 2018–2019 s.d. 2021–2022, ha" tinggi="h-64" />

        <div class="kolom-insight">
            <x-insight>
                <x-slot:terlihat>
                    Deforestasi netto memuncak pada {{ $deforestasi['puncak'] }}, lalu turun tajam dan bertahan di kisaran 100–120 rb ha per tahun.
                    {{ F::persen($deforestasi['dalam_persen'], 0) }} deforestasi 2018–2022 terjadi <em>di dalam</em> kawasan hutan, dan lima provinsi teratas
                    menyumbang {{ F::persen($deforestasi['top5_persen'], 0) }}.
                </x-slot:terlihat>
                <x-slot:kenapa>
                    Penurunan sejak 2019–2020 bertepatan dengan moratorium permanen izin baru di hutan primer dan gambut (Inpres 5/2019) serta tahun-tahun
                    basah 2020–2022. Lonjakan 2018–2019 sejalan dengan kebakaran besar 2019 (bagian 4). Kalimantan dan Sumatera mendominasi karena di sana
                    ekspansi perkebunan dan hutan tanaman paling luas.
                </x-slot:kenapa>
                <x-slot:lanjut>Apa yang menggerus hutan? Dua tersangka utama: kebakaran (bagian 4) dan penebangan untuk kayu (bagian 5).</x-slot:lanjut>
            </x-insight>
        </div>
    </div>
</x-bagian>
