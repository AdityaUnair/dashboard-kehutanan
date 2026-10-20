<?php

namespace App\Services;

use App\Models\Deforestasi;
use App\Models\EtlLog;
use App\Models\Karhutla;
use App\Models\KawasanHutan;
use App\Models\ProduksiKayu;
use App\Models\Provinsi;
use App\Models\ReplicationLog;
use App\Models\SyncLog;
use App\Models\TutupanLahan;
use Illuminate\Support\Collection;

/**
 * Menyiapkan data siap-tampil untuk dashboard. Semua angka dibaca dari kehutanan_dashboard (replika).
 */
class DashboardService
{
    private const TAHUN_AWAL = 2018;

    /** Karhutla tahun berjalan: datanya baru s.d. Juli, jadi hanya untuk peta & peringkat, tidak untuk tren. */
    private const KARHUTLA_PARSIAL = 2026;

    private const LABEL_PARSIAL = '2026 (Jan–Jul)';


    private ?Collection $provinsi = null;

    private function provinsi(): Collection
    {
        return $this->provinsi ??= Provinsi::orderBy('kode_bps')->get()->keyBy('kode_bps');
    }

    private function nama(string $kode): string
    {
        return $this->provinsi()[$kode]->nama ?? $kode;
    }

    /** Kalimantan Utara digabung ke Kalimantan Timur agar setara dengan D1, D2, D5 (lihat catatan sumber di data/raw/SUMBER_DATA.md). */
    private function kodeGabungan(string $kode): string
    {
        return $kode === '65' ? '64' : $kode;
    }

    private function persen(?float $baru, ?float $lama): ?float
    {
        return $lama ? ($baru - $lama) / $lama * 100 : null;
    }

    // ------------------------------------------------------------------ Ringkasan

    public function kpi(): array
    {
        $kawasanTahun = KawasanHutan::max('tahun');
        $tutupanTahun = TutupanLahan::max('tahun');
        $tutupan = TutupanLahan::where('tahun', $tutupanTahun)->selectRaw('SUM(hutan_ha) h, SUM(total_ha) t')->first();
        $tutupanAwal = TutupanLahan::where('tahun', self::TAHUN_AWAL)->sum('hutan_ha');

        $karhutla = Karhutla::where('tahun', '<', self::KARHUTLA_PARSIAL)->selectRaw('tahun, SUM(luas_ha) luas')->groupBy('tahun')->pluck('luas', 'tahun');
        $kayu = ProduksiKayu::selectRaw('tahun, SUM(produksi_m3) m3')->groupBy('tahun')->pluck('m3', 'tahun');
        $defor = Deforestasi::selectRaw('periode, SUM(total_ha) total')->groupBy('periode')->orderBy('periode')->pluck('total', 'periode');

        $deret = fn (Collection $c) => $c->map(fn ($v) => (float) $v)->all();

        return [
            // deret nasional per tahun untuk sparkline di kartu ringkasan
            'deret' => [
                'kawasan' => $deret(KawasanHutan::selectRaw('tahun, SUM(total_ha) v')->groupBy('tahun')->orderBy('tahun')->pluck('v', 'tahun')),
                'tutupan' => $deret(TutupanLahan::selectRaw('tahun, SUM(hutan_ha) v')->groupBy('tahun')->orderBy('tahun')->pluck('v', 'tahun')),
                'deforestasi' => $deret($defor),
                'karhutla' => $deret($karhutla),
                'kayu' => $deret($kayu),
            ],
            'kawasan' => ['tahun' => $kawasanTahun, 'nilai' => (float) KawasanHutan::where('tahun', $kawasanTahun)->sum('total_ha')],
            'tutupan' => [
                'tahun' => $tutupanTahun, 'nilai' => (float) $tutupan->h, 'persen' => $tutupan->h / $tutupan->t * 100,
                'perubahan' => $this->persen($tutupan->h, $tutupanAwal),
            ],
            'karhutla' => [
                'tahun' => $karhutla->keys()->last(), 'nilai' => (float) $karhutla->last(),
                'puncak_tahun' => $karhutla->sortDesc()->keys()->first(), 'puncak' => (float) $karhutla->max(),
            ],
            'kayu' => [
                'tahun' => $kayu->keys()->last(), 'nilai' => (float) $kayu->last(),
                'perubahan' => $this->persen($kayu->last(), $kayu[self::TAHUN_AWAL] ?? null),
            ],
            'deforestasi' => [
                'periode' => $defor->keys()->last(), 'nilai' => (float) $defor->last(),
                'perubahan' => $this->persen($defor->last(), $defor['2018-2019'] ?? null),
            ],
        ];
    }

    // ------------------------------------------------------------------ D1

    public function kawasanHutan(): array
    {
        $tahun = KawasanHutan::max('tahun');
        $rows = KawasanHutan::where('tahun', $tahun)->orderByDesc('total_ha')->get();
        $total = $rows->sum('total_ha');

        $fungsi = [
            'Hutan Lindung' => $rows->sum('hutan_lindung_ha'),
            'Konservasi (KSA/KPA)' => $rows->sum('konservasi_ha'),
            'Produksi Terbatas' => $rows->sum('produksi_terbatas_ha'),
            'Produksi Tetap' => $rows->sum('produksi_tetap_ha'),
            'Produksi Konversi' => $rows->sum('produksi_konversi_ha'),
        ];
        $top3 = $rows->take(3);
        $nasional = KawasanHutan::selectRaw('tahun, SUM(total_ha) total')->groupBy('tahun')->orderBy('tahun')->pluck('total', 'tahun');

        return [
            'tahun' => $tahun,
            'awal' => ['tahun' => $nasional->keys()->first(), 'total' => (float) $nasional->first()],
            'akhir' => ['tahun' => $nasional->keys()->last(), 'total' => (float) $nasional->last()],
            'provinsi' => $rows->map(fn ($r) => [
                'nama' => $this->nama($r->kode_bps),
                'lindung' => (float) $r->hutan_lindung_ha,
                'konservasi' => (float) $r->konservasi_ha,
                'produksi' => (float) ($r->produksi_terbatas_ha + $r->produksi_tetap_ha + $r->produksi_konversi_ha),
            ])->values(),
            'fungsi' => $fungsi,
            'nasional' => $nasional,
            'top3' => $top3->map(fn ($r) => $this->nama($r->kode_bps))->values(),
            'top3_persen' => $top3->sum('total_ha') / $total * 100,
            'papua_persen' => $rows->whereIn('kode_bps', ['91', '94'])->sum('total_ha') / $total * 100,
            'produksi_persen' => ($fungsi['Produksi Terbatas'] + $fungsi['Produksi Tetap'] + $fungsi['Produksi Konversi']) / $total * 100,
        ];
    }

    // ------------------------------------------------------------------ D2

    public function tutupanLahan(): array
    {
        $nasional = TutupanLahan::selectRaw('tahun, SUM(hutan_ha) hutan, SUM(total_ha) total')
            ->groupBy('tahun')->orderBy('tahun')->get();
        $akhir = TutupanLahan::max('tahun');

        $awal = TutupanLahan::where('tahun', self::TAHUN_AWAL)->pluck('hutan_ha', 'kode_bps');
        $perubahan = TutupanLahan::where('tahun', $akhir)->get()
            ->map(fn ($r) => ['nama' => $this->nama($r->kode_bps), 'selisih' => $r->hutan_ha - ($awal[$r->kode_bps] ?? 0),
                'persen' => (float) $r->persen_hutan])
            ->sortBy('selisih')->values();

        return [
            'tahun_akhir' => $akhir,
            // Rentang tahun lengkap; tahun yang dibuang saat transformasi (2020) tetap muncul sebagai celah
            'tahun' => range(self::TAHUN_AWAL, $akhir),
            'nasional' => $nasional->map(fn ($r) => ['tahun' => $r->tahun, 'hutan' => (float) $r->hutan,
                'persen' => $r->hutan / $r->total * 100]),
            'perubahan' => $perubahan->take(8)->concat($perubahan->reverse()->take(5)->reverse())->values(),
            'jumlah_turun' => $perubahan->where('selisih', '<', 0)->count(),
            'jumlah_provinsi' => $perubahan->count(),
        ];
    }

    // ------------------------------------------------------------------ D5

    public function deforestasi(): array
    {
        $nasional = Deforestasi::selectRaw('periode, SUM(dalam_kawasan_ha) dalam, SUM(luar_kawasan_ha) luar, SUM(total_ha) total')
            ->groupBy('periode')->orderBy('periode')->get();

        $provinsi = Deforestasi::where('tahun_awal', '>=', self::TAHUN_AWAL)
            ->selectRaw('kode_bps, SUM(total_ha) total, SUM(dalam_kawasan_ha) dalam')
            ->groupBy('kode_bps')->orderByDesc('total')->get();
        $totalSemua = $provinsi->sum('total');

        $puncak = $nasional->sortByDesc('total')->first();

        return [
            'nasional' => $nasional->map(fn ($r) => ['periode' => $r->periode, 'dalam' => (float) $r->dalam,
                'luar' => (float) $r->luar, 'total' => (float) $r->total]),
            'top' => $provinsi->take(10)->map(fn ($r) => ['nama' => $this->nama($r->kode_bps), 'total' => (float) $r->total])->values(),
            'top5_persen' => $provinsi->take(5)->sum('total') / $totalSemua * 100,
            'dalam_persen' => $provinsi->sum('dalam') / $totalSemua * 100,
            'puncak' => $puncak->periode,
            'reforestasi' => Deforestasi::where('tahun_awal', '>=', self::TAHUN_AWAL)->where('total_ha', '<', 0)
                ->count(),
        ];
    }

    // ------------------------------------------------------------------ D3 & D4 (pola sama: nasional + matriks provinsi x tahun)

    private function matriks(Collection $rows, string $kolom): array
    {
        $tahun = $rows->pluck('tahun')->unique()->sort()->values();
        $nasional = $rows->groupBy('tahun')->map(fn ($g) => (float) $g->sum($kolom))->sortKeys();
        $perProvinsi = $rows->groupBy('kode_bps')->map(fn ($g, $kode) => [
            'nama' => $this->nama($kode),
            'nilai' => $tahun->map(fn ($t) => (float) ($g->firstWhere('tahun', $t)?->{$kolom} ?? 0))->values(),
        ])->values();

        return ['tahun' => $tahun, 'nasional' => $nasional, 'provinsi' => $perProvinsi];
    }

    public function karhutla(): array
    {
        $m = $this->matriks(Karhutla::all(), 'luas_ha');
        $parsial = $m['nasional'][self::KARHUTLA_PARSIAL] ?? null;
        $m['nasional'] = $m['nasional']->except(self::KARHUTLA_PARSIAL); // puncak & sorotan dihitung dari tahun penuh saja
        $puncak = $m['nasional']->sortDesc()->keys()->first();
        $rows2019 = Karhutla::where('tahun', $puncak)->get();
        $wilayah = $rows2019->groupBy(fn ($r) => $this->provinsi()[$r->kode_bps]->wilayah)
            ->map(fn ($g) => $g->sum('luas_ha'))->sortDesc();

        return $m + [
            'puncak' => $puncak,
            'label_tahun' => [self::KARHUTLA_PARSIAL => self::LABEL_PARSIAL],
            'parsial' => $parsial === null ? null : ['label' => self::LABEL_PARSIAL, 'nilai' => $parsial],
            'puncak_kedua' => $m['nasional']->sortDesc()->keys()->get(1),
            'sorot' => $m['nasional']->sortDesc()->keys()->take(2)->values(),
            'wilayah_puncak' => $wilayah->keys()->take(2)->values(),
            'wilayah_puncak_persen' => $wilayah->take(2)->sum() / $wilayah->sum() * 100,
        ];
    }

    public function produksiKayu(): array
    {
        $m = $this->matriks(ProduksiKayu::all(), 'produksi_m3');
        $akhir = $m['tahun']->last();
        $rows = ProduksiKayu::where('tahun', $akhir)->orderByDesc('produksi_m3')->get();

        return $m + [
            'puncak' => $m['nasional']->sortDesc()->keys()->first(),
            'teratas' => $this->nama($rows->first()->kode_bps),
            'teratas_persen' => $rows->first()->produksi_m3 / $rows->sum('produksi_m3') * 100,
            'top3_persen' => $rows->take(3)->sum('produksi_m3') / $rows->sum('produksi_m3') * 100,
        ];
    }

    // ------------------------------------------------------------------ Gabungan

    /** G1: tren gabungan dalam indeks (2018 = 100) supaya satuan berbeda bisa dibandingkan. */
    public function indeksGabungan(): array
    {
        $deret = [
            'Tutupan hutan (D2)' => TutupanLahan::selectRaw('tahun, SUM(hutan_ha) v')->groupBy('tahun')->pluck('v', 'tahun'),
            'Karhutla (D3)' => Karhutla::where('tahun', '<', self::KARHUTLA_PARSIAL)->selectRaw('tahun, SUM(luas_ha) v')->groupBy('tahun')->pluck('v', 'tahun'),
            'Produksi kayu (D4)' => ProduksiKayu::selectRaw('tahun, SUM(produksi_m3) v')->groupBy('tahun')->pluck('v', 'tahun'),
            'Deforestasi netto (D5)' => Deforestasi::selectRaw('tahun_akhir tahun, SUM(total_ha) v')->groupBy('tahun_akhir')->pluck('v', 'tahun'),
        ];
        $tahun = range(self::TAHUN_AWAL, max(array_map(fn ($d) => $d->keys()->max(), $deret)));

        $hasil = [];
        foreach ($deret as $label => $d) {
            $dasar = $d[self::TAHUN_AWAL] ?? null;
            $hasil[$label] = array_map(fn ($t) => isset($d[$t]) && $dasar ? round($d[$t] / $dasar * 100, 1) : null, $tahun);
        }

        return ['tahun' => $tahun, 'deret' => $hasil];
    }

    /** G2: luas kawasan hutan (status hukum) vs tutupan hutan sebenarnya (citra satelit), tahun yang sama. */
    public function kawasanVsTutupan(): array
    {
        $tahun = min(KawasanHutan::max('tahun'), TutupanLahan::max('tahun'));
        $kawasan = KawasanHutan::where('tahun', $tahun)->pluck('total_ha', 'kode_bps');
        $hutan = TutupanLahan::where('tahun', $tahun)->pluck('hutan_ha', 'kode_bps');

        $rows = $kawasan->map(fn ($k, $kode) => [
            'nama' => $this->nama($kode),
            'kawasan' => (float) $k,
            'hutan' => (float) ($hutan[$kode] ?? 0),
            'rasio' => $k ? ($hutan[$kode] ?? 0) / $k * 100 : null,
        ])->sortByDesc('kawasan')->values();

        $rendah = $rows->where('kawasan', '>', 500000)->sortBy('rasio')->take(5)->values();

        return [
            'tahun' => $tahun,
            'provinsi' => $rows->take(15)->values(),
            'rasio_nasional' => $hutan->sum() / $kawasan->sum() * 100,
            'rasio_rendah' => $rendah,
        ];
    }

    /** G3: tiap provinsi = satu titik. Karhutla vs deforestasi, ukuran titik = produksi kayu (2018-2022). */
    public function benangMerahProvinsi(): array
    {
        $akhir = 2022;
        $kumpul = fn (Collection $rows, string $kolom) => $rows
            ->groupBy(fn ($r) => $this->kodeGabungan($r->kode_bps))
            ->map(fn ($g) => (float) $g->sum($kolom));

        $karhutla = $kumpul(Karhutla::whereBetween('tahun', [self::TAHUN_AWAL, $akhir])->get(), 'luas_ha');
        $kayu = $kumpul(ProduksiKayu::whereBetween('tahun', [self::TAHUN_AWAL, $akhir])->get(), 'produksi_m3');
        $defor = $kumpul(Deforestasi::where('tahun_awal', '>=', self::TAHUN_AWAL)->where('tahun_akhir', '<=', $akhir)->get(), 'total_ha');

        // Maksimal 3 warna agar tetap terbedakan (termasuk buta warna): dua pulau utama + sisanya
        $kelompok = fn ($wilayah) => in_array($wilayah, ['Sumatera', 'Kalimantan']) ? $wilayah : 'Wilayah lain';

        $titik = $defor->map(fn ($d, $kode) => [
            'nama' => $kode === '64' ? 'Kalimantan Timur + Utara' : $this->nama($kode),
            'wilayah' => $kelompok($this->provinsi()[$kode]->wilayah),
            'karhutla' => $karhutla[$kode] ?? 0,
            'deforestasi' => $d,
            'kayu' => $kayu[$kode] ?? 0,
        ])->values();

        return [
            'periode' => self::TAHUN_AWAL.'-'.$akhir,
            'titik' => $titik,
            'r_karhutla_defor' => $this->korelasi($titik->pluck('karhutla'), $titik->pluck('deforestasi')),
            'r_kayu_defor' => $this->korelasi($titik->pluck('kayu'), $titik->pluck('deforestasi')),
            'wilayah' => ['Sumatera', 'Kalimantan', 'Wilayah lain'],
        ];
    }

    private function korelasi(Collection $x, Collection $y): ?float
    {
        $n = $x->count();
        $mx = $x->avg();
        $my = $y->avg();
        $sxy = $sxx = $syy = 0;
        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $mx;
            $dy = $y[$i] - $my;
            $sxy += $dx * $dy;
            $sxx += $dx * $dx;
            $syy += $dy * $dy;
        }

        return $sxx && $syy ? $sxy / sqrt($sxx * $syy) : null;
    }

    /** G4: validasi silang - perubahan tutupan hutan (D2) vs total deforestasi netto (D5) untuk 2018-2022. */
    public function validasiTutupanVsDeforestasi(): array
    {
        $h2018 = (float) TutupanLahan::where('tahun', 2018)->sum('hutan_ha');
        $h2022 = (float) TutupanLahan::where('tahun', 2022)->sum('hutan_ha');
        $defor = (float) Deforestasi::where('tahun_awal', '>=', 2018)->where('tahun_akhir', '<=', 2022)->sum('total_ha');

        return [
            'perubahan_tutupan' => $h2022 - $h2018,
            'perkiraan_dari_deforestasi' => -$defor,
            'selisih' => ($h2022 - $h2018) + $defor,
        ];
    }

    // ------------------------------------------------------------------ Peta sebaran

    /** Nilai per provinsi untuk setiap dataset & tahun, plus posisi petak. Dipakai peta sebaran (dropdown dataset/tahun). */
    public function sebaran(): array
    {
        $perTahun = function (Collection $rows, string $kolomTahun, string $kolomNilai) {
            return $rows->groupBy($kolomTahun)->sortKeys()
                ->map(fn ($g) => $g->mapWithKeys(fn ($r) => [$r->kode_bps => round((float) $r->{$kolomNilai}, 2)]));
        };

        $dataset = [
            'D2' => [
                'label' => 'Tutupan hutan (% daratan)', 'satuan' => '%', 'skala' => 'sekuensial', 'kaltaraDigabung' => true,
                'nilai' => $perTahun(TutupanLahan::all(), 'tahun', 'persen_hutan'),
                'catatan' => 'Persen daratan provinsi yang masih berhutan. Tahun 2020 tidak ada: dibuang oleh pemeriksaan ETL karena nilainya mustahil (BPS tidak memberi catatan).',
            ],
            'D1' => [
                'label' => 'Luas kawasan hutan', 'satuan' => 'ha', 'skala' => 'sekuensial', 'kaltaraDigabung' => true,
                'nilai' => $perTahun(KawasanHutan::all(), 'tahun', 'total_ha'),
                'catatan' => 'Status hukum (SK Menteri), bukan kondisi lapangan.',
            ],
            'D5' => [
                'label' => 'Deforestasi netto', 'satuan' => 'ha/th', 'skala' => 'divergen', 'kaltaraDigabung' => true,
                'nilai' => $perTahun(Deforestasi::all(), 'periode', 'total_ha'),
                'catatan' => 'Merah karat = hutan berkurang, hijau = reforestasi lebih besar (netto negatif).',
            ],
            'D3' => [
                'label' => 'Luas kebakaran hutan & lahan', 'satuan' => 'ha', 'skala' => 'sekuensial', 'kaltaraDigabung' => false,
                'nilai' => $perTahun(Karhutla::all(), 'tahun', 'luas_ha')->keyBy(fn ($v, $t) => $t == self::KARHUTLA_PARSIAL ? self::LABEL_PARSIAL : $t),
                'catatan' => 'Luas indikatif terbakar menurut SiPongi. Pilihan 2026 hanya berisi data Januari–Juli, jadi belum bisa dibandingkan dengan tahun penuh.',
            ],
            'D4' => [
                'label' => 'Produksi kayu bulat', 'satuan' => 'm³', 'skala' => 'sekuensial', 'kaltaraDigabung' => false,
                'nilai' => $perTahun(ProduksiKayu::all(), 'tahun', 'produksi_m3'),
            ],
        ];

        return [
            'provinsi' => $this->provinsi()->map(fn ($p) => ['kode' => $p->kode_bps, 'nama' => $p->nama, 'wilayah' => $p->wilayah])->values(),
            'dataset' => $dataset,
        ];
    }

    // ------------------------------------------------------------------ Status integrasi (bukti ETL, replikasi, sinkronisasi)

    public function statusIntegrasi(): array
    {
        $runTerakhir = EtlLog::max('run_id');
        $replikasi = ReplicationLog::whereIn('id', ReplicationLog::selectRaw('MAX(id)')->groupBy('tabel'))
            ->orderBy('id')->get();

        return [
            'sumber' => [
                ['kode' => 'D1', 'nama' => 'Luas kawasan hutan', 'penerbit' => 'BPS', 'format' => 'XLSX', 'cara' => 'Unduh file (6 tahun)'],
                ['kode' => 'D2', 'nama' => 'Tutupan lahan hutan & non-hutan', 'penerbit' => 'BPS / KLHK', 'format' => 'CSV', 'cara' => 'Unduh file'],
                ['kode' => 'D3', 'nama' => 'Luas kebakaran hutan & lahan', 'penerbit' => 'SiPongi KLHK', 'format' => 'XLSX', 'cara' => 'Unduh file'],
                ['kode' => 'D5', 'nama' => 'Deforestasi netto', 'penerbit' => 'BPS / KLHK', 'format' => 'CSV', 'cara' => 'Unduh file'],
            ],
            'etl' => EtlLog::where('run_id', $runTerakhir)->orderBy('id')->get(),
            'replikasi' => $replikasi,
            'sync' => SyncLog::orderByDesc('id')->first(),
        ];
    }
}
