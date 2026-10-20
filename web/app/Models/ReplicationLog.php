<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catatan replikasi master -> dashboard (replika), termasuk checksum. */
class ReplicationLog extends Model
{
    protected $table = 'replication_log';

    public $timestamps = false;

    public $incrementing = false;
}
