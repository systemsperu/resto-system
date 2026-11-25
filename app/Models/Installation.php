<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Installation extends Model
{
    protected $fillable = ['uuid', 'name', 'mode', 'device_uuid', 'sync_status', 'last_synced_at', 'last_error'];

    protected $casts = ['last_synced_at' => 'datetime'];
}
