<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponRedemption extends Model
{
    protected $fillable = ['coupon_id', 'client_id', 'order_id', 'idempotency_key', 'discount_amount', 'redeemed_at'];

    protected $casts = ['redeemed_at' => 'datetime', 'discount_amount' => 'decimal:2'];
}
