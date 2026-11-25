<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['customer_segment_id', 'name', 'channel', 'subject', 'message', 'status', 'scheduled_at', 'sent_at', 'created_by'];

    protected $casts = ['scheduled_at' => 'datetime', 'sent_at' => 'datetime'];

    public function segment()
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

    public function deliveries()
    {
        return $this->hasMany(CampaignDelivery::class);
    }
}
