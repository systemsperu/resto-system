<?php

namespace App\Services\Lodging;

use App\Models\AccommodationBlock;
use App\Models\AccommodationUnit;
use App\Models\Client;
use App\Models\HousekeepingTask;
use App\Models\LodgingReservation;
use App\Models\Payment;
use App\Models\SystemAudit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LodgingService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function reserve(array $data, User $user): LodgingReservation
    {
        return DB::transaction(function () use ($data, $user) {
            $unit = AccommodationUnit::query()->lockForUpdate()->findOrFail($data['accommodation_unit_id']);
            $checkIn = CarbonImmutable::parse($data['check_in']);
            $checkOut = CarbonImmutable::parse($data['check_out']);
            $guests = (int) $data['adults'] + (int) ($data['children'] ?? 0);
            $quote = $this->availability->available($checkIn, $checkOut, $guests)->firstWhere('id', $unit->id);

            if (! $quote) {
                throw new \DomainException('La unidad ya no está disponible para esas fechas y ocupación.');
            }

            $reservation = LodgingReservation::create([
                ...$data,
                'public_id' => (string) Str::uuid(),
                'created_by' => $user->id,
                'nightly_rate' => $quote->quote_nightly_rate,
                'total_amount' => $quote->quote_total,
                'status' => 'confirmed',
            ]);
            $reservation->guests()->create(['full_name' => Client::findOrFail($data['client_id'])->name, 'is_primary' => true]);
            $this->audit($user, 'lodging.reservation.create', 'lodging_reservation:'.$reservation->id, ['unit_id' => $unit->id]);

            return $reservation;
        }, 3);
    }

    public function transition(LodgingReservation $reservation, string $target, User $user): LodgingReservation
    {
        return DB::transaction(function () use ($reservation, $target, $user) {
            $reservation = LodgingReservation::query()->lockForUpdate()->findOrFail($reservation->id);
            $unit = AccommodationUnit::query()->lockForUpdate()->findOrFail($reservation->accommodation_unit_id);
            $allowed = ['confirmed' => ['checked_in', 'cancelled'], 'checked_in' => ['checked_out']];

            if (! in_array($target, $allowed[$reservation->status] ?? [], true)) {
                throw new \DomainException('La transición solicitada no corresponde al estado actual.');
            }

            if ($target === 'checked_in') {
                if (today()->lt($reservation->check_in)) {
                    throw new \DomainException('No se puede registrar la entrada antes de la fecha reservada.');
                }
                $reservation->checked_in_at = now();
                $unit->update(['status' => 'occupied']);
            } elseif ($target === 'checked_out') {
                $reservation->checked_out_at = now();
                $unit->update(['status' => 'cleaning']);
                HousekeepingTask::firstOrCreate(
                    ['lodging_reservation_id' => $reservation->id, 'task_type' => 'checkout_cleaning'],
                    ['accommodation_unit_id' => $unit->id, 'scheduled_for' => today(), 'status' => 'pending'],
                );
            }

            $reservation->status = $target;
            $reservation->save();
            $this->audit($user, 'lodging.reservation.'.$target, 'lodging_reservation:'.$reservation->id);

            return $reservation;
        }, 3);
    }

    public function addPayment(LodgingReservation $reservation, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($reservation, $data, $user) {
            $reservation = LodgingReservation::query()->with('payments')->lockForUpdate()->findOrFail($reservation->id);
            if (in_array($reservation->status, ['cancelled'], true) || (float) $data['amount'] > $reservation->balance) {
                throw new \DomainException('El pago no es válido o supera el saldo de la reserva.');
            }

            $payment = Payment::create([
                'lodging_reservation_id' => $reservation->id,
                'source' => 'lodging',
                'user_id' => $user->id,
                'method' => $data['method'],
                'amount' => $data['amount'],
                'reference' => $data['reference'] ?? null,
                'status' => 'confirmed',
                'paid_at' => now(),
            ]);
            $this->audit($user, 'lodging.payment.create', 'payment:'.$payment->id, ['reservation_id' => $reservation->id]);

            return $payment;
        }, 3);
    }

    public function block(AccommodationUnit $unit, array $data, User $user): AccommodationBlock
    {
        return DB::transaction(function () use ($unit, $data, $user) {
            $unit = AccommodationUnit::query()->lockForUpdate()->findOrFail($unit->id);
            $overlap = $unit->reservations()->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in', '<', $data['ends_on'])->where('check_out', '>', $data['starts_on'])->exists();
            if ($overlap) {
                throw new \DomainException('Existe una reserva activa dentro del periodo de bloqueo.');
            }

            $block = $unit->blocks()->create([...$data, 'created_by' => $user->id]);
            if (CarbonImmutable::parse($data['starts_on'])->lte(today()) && CarbonImmutable::parse($data['ends_on'])->gt(today())) {
                $unit->update(['status' => 'maintenance']);
            }
            $this->audit($user, 'lodging.unit.block', 'accommodation_block:'.$block->id, ['unit_id' => $unit->id]);

            return $block;
        }, 3);
    }

    public function completeCleaning(HousekeepingTask $task, User $user): void
    {
        DB::transaction(function () use ($task, $user) {
            $task = HousekeepingTask::query()->lockForUpdate()->findOrFail($task->id);
            if ($task->status === 'completed') {
                return;
            }
            $task->update(['status' => 'completed', 'completed_by' => $user->id, 'completed_at' => now()]);
            $unit = AccommodationUnit::query()->lockForUpdate()->findOrFail($task->accommodation_unit_id);
            $blocked = $unit->blocks()->whereNull('released_at')->where('starts_on', '<=', today())->where('ends_on', '>', today())->exists();
            $unit->update(['status' => $blocked ? 'maintenance' : 'available']);
            $this->audit($user, 'lodging.cleaning.complete', 'housekeeping_task:'.$task->id);
        }, 3);
    }

    public function releaseBlock(AccommodationBlock $block, User $user): void
    {
        DB::transaction(function () use ($block, $user) {
            $block = AccommodationBlock::query()->lockForUpdate()->findOrFail($block->id);
            if ($block->released_at) {
                return;
            }
            $block->update(['released_at' => now()]);
            $unit = AccommodationUnit::query()->lockForUpdate()->findOrFail($block->accommodation_unit_id);
            $hasOtherBlock = $unit->blocks()->whereNull('released_at')->whereKeyNot($block->id)->where('starts_on', '<=', today())->where('ends_on', '>', today())->exists();
            $isOccupied = $unit->reservations()->where('status', 'checked_in')->exists();
            $unit->update(['status' => $isOccupied ? 'occupied' : ($hasOtherBlock ? 'maintenance' : 'available')]);
            $this->audit($user, 'lodging.unit.release', 'accommodation_block:'.$block->id, ['unit_id' => $unit->id]);
        }, 3);
    }

    private function audit(User $user, string $action, string $target, array $metadata = []): void
    {
        SystemAudit::create(['user_id' => $user->id, 'action' => $action, 'target' => $target, 'status' => 'success', 'metadata' => $metadata]);
    }
}
