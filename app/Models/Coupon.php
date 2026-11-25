<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'name', 'discount_type', 'discount_value', 'minimum_amount', 'usage_limit', 'per_client_limit', 'used_count', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'discount_value' => 'decimal:2', 'minimum_amount' => 'decimal:2'];

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }
}
