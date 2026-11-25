<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncInbox extends Model
{
    protected $fillable = ['event_uuid', 'source_installation_uuid', 'event_type', 'payload', 'checksum', 'status', 'processed_at', 'last_error'];

    protected $casts = ['payload' => 'array', 'processed_at' => 'datetime'];
}
