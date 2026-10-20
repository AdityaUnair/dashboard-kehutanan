<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catatan setiap tahap ETL (extract, transform-load) beserta hasil cek kualitas. */
class EtlLog extends Model
{
    protected $table = 'etl_log';

    public $timestamps = false;

    public $incrementing = false;
}
