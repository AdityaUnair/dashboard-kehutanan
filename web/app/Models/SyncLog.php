<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table = 'sync_log';

    public $timestamps = false;

    public $incrementing = false;
}
