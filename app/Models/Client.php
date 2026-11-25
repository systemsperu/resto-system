<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'document_type', 'document_number', 'phone', 'email', 'address', 'birth_date', 'source', 'preferences'];

    protected $casts = ['birth_date' => 'date', 'preferences' => 'array', 'last_visit_at' => 'datetime'];

    // Relación: Un cliente tiene muchas órdenes
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function consents()
    {
        return $this->hasMany(ClientConsent::class);
    }

    public function segments()
    {
        return $this->belongsToMany(CustomerSegment::class);
    }

    public function loyaltyAccount()
    {
        return $this->hasOne(LoyaltyAccount::class);
    }

    public function lodgingReservations()
    {
        return $this->hasMany(LodgingReservation::class);
    }

    public function hasConsent(string $channel): bool
    {
        return (bool) $this->consents()->where('channel', $channel)->latest('recorded_at')->value('granted');
    }
}
