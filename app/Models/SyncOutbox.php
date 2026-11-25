<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncOutbox extends Model
{
    protected $fillable = ['event_uuid', 'installation_uuid', 'aggregate_type', 'aggregate_id', 'aggregate_version', 'event_type', 'payload', 'checksum', 'status', 'attempts', 'available_at', 'acked_at', 'last_error'];

    protected $casts = ['payload' => 'array', 'available_at' => 'datetime', 'acked_at' => 'datetime'];
}
