@use('App\Support\Format', 'F')

<section class="bagian" id="integrasi">
    <div class="bagian-kepala">
        <span class="no">⚙</span><h2>Status integrasi data</h2>
        <span class="label-sumber">Data-level EAI</span>
    </div>

    <div class="grid-2-rata">
        <div class="kartu">
            <h3>Sumber data</h3>
            <div class="ket">Lima sumber, tiga format berbeda</div>
            <div class="tabel-wrap">
                <table>
                    <thead><tr><th>Kode</th><th>Data</th><th>Penerbit</th><th>Format</th><th>Cara extract</th></tr></thead>
                    <tbody>
                    @foreach ($integrasi['sumber'] as $s)
                        <tr><td><strong>{{ $s['kode'] }}</strong></td><td>{{ $s['nama'] }}</td><td>{{ $s['penerbit'] }}</td>
                            <td><span class="pil ok">{{ $s['format'] }}</span></td><td>{{ $s['cara'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="kartu">
            <h3>Replikasi master → dashboard</h3>
            <div class="ket">Status terakhir per tabel. Checksum sama = replika identik dengan master.</div>
            <div class="tabel-wrap">
                <table>
                    <thead><tr><th>Tabel</th><th class="angka">Baris</th><th class="angka">+ / ~ / −</th><th>Status</th><th>Waktu</th></tr></thead>
                    <tbody>
                    @foreach ($integrasi['replikasi'] as $r)
                        <tr>
                            <td>{{ $r->tabel }}</td>
                            <td class="angka">{{ $r->baris_master }}</td>
                            <td class="angka">{{ $r->baris_disisipkan }} / {{ $r->baris_diperbarui }} / {{ $r->baris_dihapus }}</td>
                            <td><span class="pil {{ $r->status === 'SINKRON' ? 'ok' : 'warn' }}">{{ $r->status }}</span></td>
                            <td class="kecil">{{ $r->waktu }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="kartu" style="margin-top:16px">
        <h3>Log ETL terakhir</h3>
        <div class="ket">Run {{ $integrasi['etl']->first()?->run_id }} · cek total = jumlah semua provinsi dibandingkan baris total nasional di file sumber</div>
        <div class="tabel-wrap">
            <table>
                <thead><tr><th>Tahap</th><th>Data</th><th class="angka">Masuk</th><th class="angka">Dimuat</th><th class="angka">Dibuang</th><th>Status</th><th>Catatan</th></tr></thead>
                <tbody>
                @foreach ($integrasi['etl'] as $l)
                    <tr>
                        <td>{{ $l->tahap }}</td>
                        <td><strong>{{ $l->dataset }}</strong></td>
                        <td class="angka">{{ $l->baris_masuk }}</td>
                        <td class="angka">{{ $l->baris_dimuat }}</td>
                        <td class="angka">{{ $l->baris_dibuang }}</td>
                        <td><span class="pil {{ $l->status === 'OK' ? 'ok' : 'warn' }}">{{ $l->status }}</span></td>
                        <td class="kecil">{{ $l->pesan }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="kartu" style="margin-top:16px">
        @if ($sync = $integrasi['sync'])
            <div class="ket">Terakhir: {{ $sync->waktu }} · one-way (BPS → database kita)</div>
            <div class="angka-besar">
                <div><span>Baris dicek</span><strong>{{ $sync->baris_dicek }}</strong></div>
                <div><span>Baru / berubah</span><strong>{{ $sync->baris_baru }} / {{ $sync->baris_berubah }}</strong></div>
                <div><span>Sama (tidak disentuh)</span><strong>{{ $sync->baris_sama }}</strong></div>
            </div>
            @foreach (json_decode($sync->detail ?? '[]', true) as $d)
                <div class="kecil">Provinsi {{ $d['kode_bps'] }} tahun {{ $d['tahun'] }}: {{ F::angka($d['lama'], 2) }} → {{ F::angka($d['baru'], 2) }} m³</div>
            @endforeach
        @else
        @endif
    </div>
</section>
