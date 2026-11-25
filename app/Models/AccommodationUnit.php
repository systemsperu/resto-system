<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationUnit extends Model
{
    protected $fillable = ['accommodation_type_id', 'name', 'code', 'capacity', 'status', 'location', 'amenities', 'media', 'is_active'];

    protected $casts = ['amenities' => 'array', 'media' => 'array', 'is_active' => 'boolean'];

    public function type()
    {
        return $this->belongsTo(AccommodationType::class, 'accommodation_type_id');
    }

    public function reservations()
    {
        return $this->hasMany(LodgingReservation::class);
    }

    public function blocks()
    {
        return $this->hasMany(AccommodationBlock::class);
    }

    public function housekeepingTasks()
    {
        return $this->hasMany(HousekeepingTask::class);
    }
}
