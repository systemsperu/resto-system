<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationBlock extends Model
{
    protected $fillable = ['accommodation_unit_id', 'created_by', 'starts_on', 'ends_on', 'reason', 'notes', 'released_at'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'released_at' => 'datetime'];

    public function unit()
    {
        return $this->belongsTo(AccommodationUnit::class, 'accommodation_unit_id');
    }
}
