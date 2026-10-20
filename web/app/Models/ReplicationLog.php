<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catatan replikasi master -> replika (mode master-slave atau script), termasuk checksum. */
class ReplicationLog extends Model
{
    protected $connection = 'data';

    protected $table = 'replication_log';

    public $timestamps = false;

    public $incrementing = false;
}
