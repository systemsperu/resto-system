<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LodgingReservation extends Model
{
    protected $fillable = ['public_id', 'client_id', 'accommodation_unit_id', 'created_by', 'check_in', 'check_out', 'adults', 'children', 'status', 'nightly_rate', 'total_amount', 'notes', 'checked_in_at', 'checked_out_at'];

    protected $casts = ['check_in' => 'date', 'check_out' => 'date', 'nightly_rate' => 'decimal:2', 'total_amount' => 'decimal:2', 'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function unit()
    {
        return $this->belongsTo(AccommodationUnit::class, 'accommodation_unit_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function guests()
    {
        return $this->hasMany(LodgingGuest::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function housekeepingTasks()
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments->where('status', 'confirmed')->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->paid_amount);
    }
}
