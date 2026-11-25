<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAudit extends Model
{
    protected $fillable = ['user_id', 'action', 'target', 'status', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];
}
