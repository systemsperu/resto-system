<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientConsent extends Model
{
    protected $fillable = ['client_id', 'channel', 'granted', 'source', 'recorded_at', 'recorded_by'];

    protected $casts = ['granted' => 'boolean', 'recorded_at' => 'datetime'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
