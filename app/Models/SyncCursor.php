<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncCursor extends Model
{
    protected $fillable = ['channel', 'cursor', 'advanced_at'];

    protected $casts = ['advanced_at' => 'datetime'];
}
