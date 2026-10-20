@use('App\Support\Format', 'F')

<x-judul-panel judul="Status integrasi data"

<div class="grid gap-3">
    <ol class="grid gap-2 text-[13px] sm:grid-cols-5">
                   ['Master', 'data bersih'], ['Replika', 'dibaca website']] as $i => [$tahap, $ket])
            <li class="kartu flex items-center gap-3 p-3">
                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand text-xs font-bold text-white">{{ $i + 1 }}</span>
                <span><span class="block font-semibold text-ink">{{ $tahap }}</span><span class="text-xs text-ink-3">{{ $ket }}</span></span>
            </li>
        @endforeach
    </ol>

    <div class="grid gap-3 xl:grid-cols-3">
        <div class="kartu overflow-x-auto xl:col-span-2">
            <div class="kartu-judul">Sumber data</div>
            <div class="kartu-ket">Lima sumber, tiga format berbeda</div>
            <table class="tabel-data">
                <thead><tr><th>Kode</th><th>Data</th><th>Penerbit</th><th>Format</th><th>Cara extract</th></tr></thead>
                <tbody>
                @foreach ($integrasi['sumber'] as $s)
                    <tr><td class="font-semibold text-ink">{{ $s['kode'] }}</td><td>{{ $s['nama'] }}</td><td>{{ $s['penerbit'] }}</td>
                        <td><span class="pil-ok">{{ $s['format'] }}</span></td><td>{{ $s['cara'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="kartu">
            @if ($sync = $integrasi['sync'])
                <div class="kartu-ket">Terakhir: {{ $sync->waktu }} · satu arah (BPS → database kita), hanya baris yang berubah yang diperbarui</div>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ([['Baris dicek', $sync->baris_dicek], ['Baru / berubah', $sync->baris_baru.' / '.$sync->baris_berubah], ['Sama (tidak disentuh)', $sync->baris_sama]] as [$label, $nilai])
                        <div class="rounded-xl border border-garis bg-surface-2 px-3 py-2.5">
                            <div class="text-xs text-ink-3">{{ $label }}</div>
                            <div class="mt-1 text-2xl font-bold">{{ $nilai }}</div>
                        </div>
                    @endforeach
                </div>
                @foreach (json_decode($sync->detail ?? '[]', true) as $d)
                    <div class="mt-2 text-xs text-ink-3">Provinsi {{ $d['kode_bps'] }} tahun {{ $d['tahun'] }}: {{ F::angka($d['lama'], 2) }} → {{ F::angka($d['baru'], 2) }} m³</div>
                @endforeach
            @else
            @endif
        </div>
    </div>

    <div class="grid gap-3 xl:grid-cols-3">
        <div class="kartu overflow-x-auto">
            <div class="kartu-judul">Replikasi master → dashboard</div>
            <div class="kartu-ket">Terakhir: {{ $integrasi['replikasi']->first()?->waktu }} · checksum sama = replika identik dengan master · + disisipkan, ~ diperbarui, − dihapus</div>
            <table class="tabel-data">
                <thead><tr><th>Tabel</th><th class="angka">Baris</th><th class="angka whitespace-nowrap">+ / ~ / −</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($integrasi['replikasi'] as $r)
                    <tr>
                        <td class="font-mono text-[11.5px] text-ink">{{ $r->tabel }}</td>
                        <td class="angka">{{ $r->baris_master }}</td>
                        <td class="angka whitespace-nowrap">{{ $r->baris_disisipkan }} / {{ $r->baris_diperbarui }} / {{ $r->baris_dihapus }}</td>
                        <td><span class="{{ $r->status === 'SINKRON' ? 'pil-ok' : 'pil-cek' }}">{{ $r->status === 'SINKRON' ? '✓' : '!' }} {{ $r->status }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="kartu overflow-x-auto xl:col-span-2">
            <div class="kartu-judul">Log ETL terakhir</div>
            <div class="kartu-ket">Run {{ $integrasi['etl']->first()?->run_id }} · cek total = jumlah semua provinsi dibandingkan dengan baris total nasional di file sumber</div>
            <table class="tabel-data">
                <thead><tr><th>Tahap</th><th>Data</th><th class="angka">Masuk</th><th class="angka">Dimuat</th><th class="angka">Dibuang</th><th>Status</th><th>Catatan</th></tr></thead>
                <tbody>
                @foreach ($integrasi['etl'] as $l)
                    <tr>
                        <td>{{ $l->tahap }}</td>
                        <td class="font-semibold text-ink">{{ $l->dataset }}</td>
                        <td class="angka">{{ $l->baris_masuk }}</td>
                        <td class="angka">{{ $l->baris_dimuat }}</td>
                        <td class="angka">{{ $l->baris_dibuang }}</td>
                        <td><span class="{{ $l->status === 'OK' ? 'pil-ok' : 'pil-cek' }}">{{ $l->status === 'OK' ? '✓' : '!' }} {{ $l->status }}</span></td>
                        <td class="max-w-xl text-xs text-ink-3">{{ $l->pesan }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
