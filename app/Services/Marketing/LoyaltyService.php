<?php

namespace App\Services\Marketing;

use App\Models\Client;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    public function apply(Client $client, int $points, string $type, string $idempotencyKey, ?int $orderId = null, ?int $userId = null): LoyaltyTransaction
    {
        if ($points === 0 || ! in_array($type, ['earn', 'redeem', 'adjust'], true)) {
            throw new \InvalidArgumentException('Movimiento de puntos inválido.');
        }

        return DB::transaction(function () use ($client, $points, $type, $idempotencyKey, $orderId, $userId) {
            $existing = LoyaltyTransaction::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
            $account = LoyaltyAccount::firstOrCreate(['client_id' => $client->id]);
            $account = LoyaltyAccount::lockForUpdate()->findOrFail($account->id);
            $delta = $type === 'redeem' ? -abs($points) : $points;
            if ($account->points_balance + $delta < 0) {
                throw new \DomainException('Saldo de puntos insuficiente.');
            }
            $account->points_balance += $delta;
            if ($delta > 0) {
                $account->lifetime_points += $delta;
            }
            $account->tier = $account->lifetime_points >= 1000 ? 'oro' : ($account->lifetime_points >= 500 ? 'plata' : 'base');
            $account->save();

            return LoyaltyTransaction::create(['loyalty_account_id' => $account->id, 'order_id' => $orderId, 'type' => $type, 'points' => $delta, 'idempotency_key' => $idempotencyKey, 'created_by' => $userId]);
        });
    }
}
