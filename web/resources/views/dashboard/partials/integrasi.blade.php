@use('App\Support\Format', 'F')

<x-judul-panel judul="Status integrasi data"
               sub="Bukti proses di balik dashboard (data-level EAI): Extract → staging → Transform → master → Replication → replika, ditambah Synchronization dari file sumber D4 ke master." />

<div class="grid gap-3">
    <ol class="grid gap-2 text-[13px] sm:grid-cols-5">
        @foreach ([['Extract', 'XLS · XLSX · CSV · JSON'], ['Staging', 'teks mentah'], ['Transform + Load', 'aturan T1–T9, upsert'],
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
            <div class="kartu-ket">Lima sumber, empat format file berbeda</div>
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
            <div class="kartu-judul">Sinkronisasi file sumber → master (D4)</div>
            @if ($sync = $integrasi['sync'])
                <div class="kartu-ket">Terakhir: {{ $sync->waktu }} · satu arah (file JSON BPS terbaru → database master), hanya baris yang berubah yang diperbarui</div>
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
                <div class="kartu-ket">Belum pernah dijalankan. Jalankan <code>python etl/sync_d4.py</code>.</div>
            @endif
        </div>
    </div>

    <div class="grid gap-3 xl:grid-cols-3">
        @php
            $slave = $integrasi['slave'];
            $modeLog = $integrasi['replikasi']->last()?->mode;
        @endphp
        <div class="kartu overflow-x-auto">
            <div class="kartu-judul">Replikasi master → replika</div>
            {{-- mode yang sedang aktif: replikasi asli MariaDB (Master-Slave) atau simulasi lewat script --}}
            @if ($slave)
                <div class="mt-2 rounded-xl bg-brand-lembut p-2.5 text-[12px] leading-relaxed text-ink-2">
                    <b class="text-brand-teks">Master-Slave MariaDB</b> · slave {{ $slave['slave'] }} membaca binary log master {{ $slave['master'] }}<br>
                    <span class="{{ $slave['io'] ? 'pil-ok' : 'pil-cek' }}">{{ $slave['io'] ? '✓' : '!' }} IO</span>
                    <span class="{{ $slave['sql'] ? 'pil-ok' : 'pil-cek' }}">{{ $slave['sql'] ? '✓' : '!' }} SQL</span>
                    tertinggal {{ $slave['tertinggal'] ?? '–' }} detik · posisi {{ $slave['berkas'] }}
                    @if ($slave['galat'])<br><span class="text-peringatan">{{ $slave['galat'] }}</span>@endif
                </div>
            @elseif ($integrasi['cadangan'])
                <div class="mt-2 rounded-xl bg-peringatan-lembut p-2.5 text-[12px] leading-relaxed text-ink-2">
                    <b class="text-peringatan">Server slave tidak aktif.</b> Dashboard memakai replika cadangan (mode script). Jalankan <code>scripts/replika.sh start</code>.
                </div>
            @else
                <div class="mt-2 rounded-xl bg-surface-2 p-2.5 text-[12px] leading-relaxed text-ink-2">
                    <b class="text-ink">Mode script</b> · simulasi Master-Slave di satu server: <code>etl/replicate.py</code> menyalin perubahan master ke replika.
                    Replikasi asli MariaDB (server kedua) bisa dinyalakan dengan <code>scripts/replika.sh</code>.
                </div>
            @endif
            <div class="kartu-ket mt-2">Pemeriksaan terakhir: {{ $integrasi['replikasi']->last()?->waktu }} ({{ $modeLog }}) · checksum sama = replika identik dengan master
                @if ($modeLog === 'script') · + disisipkan, ~ diperbarui, − dihapus @endif</div>
            <table class="tabel-data">
                <thead><tr><th>Tabel</th><th class="angka">Baris</th><th class="angka whitespace-nowrap">{{ $modeLog === 'script' ? '+ / ~ / −' : 'Disalin oleh' }}</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($integrasi['replikasi'] as $r)
                    <tr>
                        <td class="font-mono text-[11.5px] text-ink">{{ $r->tabel }}</td>
                        <td class="angka">{{ $r->baris_master }}</td>
                        <td class="angka whitespace-nowrap">{{ $r->mode === 'script' ? "$r->baris_disisipkan / $r->baris_diperbarui / $r->baris_dihapus" : 'MariaDB' }}</td>
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
