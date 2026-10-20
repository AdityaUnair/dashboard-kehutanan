<x-judul-panel judul="Peta sebaran per provinsi"
               sub="Setiap kotak = satu provinsi, diletakkan kira-kira sesuai posisinya dari Aceh sampai Papua. Ukuran kotak sengaja sama supaya provinsi kecil tetap terlihat; yang membedakan hanya warnanya.">
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label for="sebaranData" class="label-kontrol">Data</label>
            <select id="sebaranData" class="pilih min-w-64">
                @foreach ($sebaran['dataset'] as $kode => $d)
                    <option value="{{ $kode }}">{{ $d['label'] }} ({{ $kode }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="sebaranTahun" class="label-kontrol">Tahun / periode</label>
            <select id="sebaranTahun" class="pilih min-w-32"></select>
        </div>
    </div>
</x-judul-panel>

<div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
    <div class="kartu">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="kartu-judul" id="sebaranJudul"></div>
                <div class="kartu-ket mb-0" id="sebaranCatatan"></div>
            </div>
            <div id="sebaranLegenda" class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[11px] text-ink-3" aria-label="Legenda warna"></div>
        </div>

        <div class="-mx-1 mt-5 overflow-x-auto px-1 pb-1">
            <div id="peta" class="grid min-w-[640px] grid-cols-13 gap-1.5" role="group" aria-label="Peta petak provinsi"></div>
        </div>
        <p class="mt-3 text-[11.5px] text-ink-3">Arahkan kursor ke kotak untuk melihat angka; klik untuk melihat tren provinsi di panel kanan.
            Kotak bergaris = data provinsi itu masih digabung ke provinsi induknya.</p>
        <details class="lihat-tabel" data-tabel-peta>
            <summary>Lihat tabel data</summary>
            <div class="isi"></div>
        </details>
    </div>

    <div class="grid content-start gap-4">
        <div class="kartu">
            <div class="text-[11px] font-medium tracking-wide text-ink-3 uppercase">Provinsi terpilih</div>
            <div class="mt-1 text-lg font-semibold tracking-tight" id="detailNama">—</div>
            <div class="text-xs text-ink-3" id="detailWilayah"></div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-[28px] leading-none font-semibold tracking-tight" id="detailNilai">—</span>
                <span class="text-sm text-ink-3" id="detailSatuan"></span>
            </div>
            <div class="mt-1.5 text-xs text-ink-2" id="detailPeringkat"></div>
            <div class="mt-4 h-36"><canvas id="chartDetailProvinsi" aria-label="Tren provinsi terpilih"></canvas></div>
        </div>
        <div class="kartu">
            <div class="kartu-judul">5 provinsi tertinggi</div>
            <div class="kartu-ket" id="top5Ket"></div>
            <ol id="top5" class="grid gap-2"></ol>
        </div>
    </div>
</div>
