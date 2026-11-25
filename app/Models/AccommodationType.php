<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationType extends Model
{
    protected $fillable = ['name', 'description', 'base_capacity', 'max_capacity', 'amenities', 'is_active'];

    protected $casts = ['amenities' => 'array', 'is_active' => 'boolean'];

    public function units()
    {
        return $this->hasMany(AccommodationUnit::class);
    }

    public function rates()
    {
        return $this->hasMany(AccommodationRate::class);
    }
}
