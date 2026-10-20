<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** D1 - Luas kawasan hutan per provinsi per tahun (ha). Sumber: BPS (XLSX). */
class KawasanHutan extends Model
{
    protected $table = 'fakta_kawasan_hutan';

    public $timestamps = false;

    public $incrementing = false;

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'kode_bps', 'kode_bps');
    }
}
