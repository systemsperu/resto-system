<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationRate extends Model
{
    protected $fillable = ['accommodation_type_id', 'name', 'starts_on', 'ends_on', 'nightly_rate', 'minimum_nights', 'weekdays', 'is_active'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'nightly_rate' => 'decimal:2', 'weekdays' => 'array', 'is_active' => 'boolean'];

    public function type()
    {
        return $this->belongsTo(AccommodationType::class, 'accommodation_type_id');
    }
}
