<?php

namespace App\Services\Marketing;

use App\Models\Client;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use Illuminate\Support\Facades\DB;

class CouponService
{
    public function redeem(Coupon $coupon, Client $client, float $amount, string $idempotencyKey, ?int $orderId = null): CouponRedemption
    {
        return DB::transaction(function () use ($coupon, $client, $amount, $idempotencyKey, $orderId) {
            $existing = CouponRedemption::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $coupon = Coupon::lockForUpdate()->findOrFail($coupon->id);
            if (! $coupon->is_active || ($coupon->starts_at && now()->lt($coupon->starts_at)) || ($coupon->ends_at && now()->gt($coupon->ends_at))) {
                throw new \DomainException('El cupón no está vigente.');
            }
            if ($amount < (float) $coupon->minimum_amount) {
                throw new \DomainException('No se alcanza el consumo mínimo.');
            }
            if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
                throw new \DomainException('El cupón agotó su límite.');
            }
            if ($coupon->redemptions()->where('client_id', $client->id)->count() >= $coupon->per_client_limit) {
                throw new \DomainException('El cliente agotó el límite del cupón.');
            }

            $discount = $coupon->discount_type === 'percentage'
                ? round($amount * min(100, (float) $coupon->discount_value) / 100, 2)
                : min($amount, (float) $coupon->discount_value);
            $redemption = CouponRedemption::create(['coupon_id' => $coupon->id, 'client_id' => $client->id, 'order_id' => $orderId, 'idempotency_key' => $idempotencyKey, 'discount_amount' => $discount, 'redeemed_at' => now()]);
            $coupon->increment('used_count');

            return $redemption;
        });
    }
}
