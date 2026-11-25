<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncConflict extends Model
{
    protected $fillable = ['conflict_uuid', 'event_uuid', 'aggregate_type', 'aggregate_id', 'local_payload', 'remote_payload', 'reason', 'status', 'resolution', 'resolved_at'];

    protected $casts = ['local_payload' => 'array', 'remote_payload' => 'array', 'resolved_at' => 'datetime'];
}
