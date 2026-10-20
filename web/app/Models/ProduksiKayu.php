<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduksiKayu extends Model
{
    protected $connection = 'data';

    protected $table = 'fakta_produksi_kayu';

    public $timestamps = false;

    public $incrementing = false;

    public function provinsi()
    {
        return $this->belongsTo(Provinsi::class, 'kode_bps', 'kode_bps');
    }
}
