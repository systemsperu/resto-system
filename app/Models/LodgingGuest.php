<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LodgingGuest extends Model
{
    protected $fillable = ['lodging_reservation_id', 'full_name', 'document_type', 'document_number', 'nationality', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function reservation()
    {
        return $this->belongsTo(LodgingReservation::class, 'lodging_reservation_id');
    }
}
