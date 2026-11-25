<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignDelivery extends Model
{
    protected $fillable = ['campaign_id', 'client_id', 'idempotency_key', 'status', 'delivered_at', 'failure_reason'];

    protected $casts = ['delivered_at' => 'datetime'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
