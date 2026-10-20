<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Referensi 34 provinsi (kode BPS). */
class Provinsi extends Model
{
    protected $table = 'ref_provinsi';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'kode_bps';

    protected $keyType = 'string';
}
