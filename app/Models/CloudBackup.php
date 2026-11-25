<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudBackup extends Model
{
    protected $fillable = ['backup_uuid', 'disk', 'path', 'checksum', 'size_bytes', 'encrypted', 'verified', 'status', 'verified_at', 'last_error'];

    protected $casts = ['encrypted' => 'boolean', 'verified' => 'boolean', 'verified_at' => 'datetime'];
}
