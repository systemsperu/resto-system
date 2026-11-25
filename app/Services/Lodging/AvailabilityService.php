<?php

namespace App\Services\Lodging;

use App\Models\AccommodationRate;
use App\Models\AccommodationUnit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function available(CarbonImmutable $checkIn, CarbonImmutable $checkOut, int $guests = 1): Collection
    {
        if ($checkOut->lte($checkIn)) {
            throw new \InvalidArgumentException('La salida debe ser posterior a la entrada.');
        }

        $nights = $checkIn->diffInDays($checkOut);
        if ($nights > 90) {
            throw new \InvalidArgumentException('La consulta no puede superar 90 noches.');
        }

        return AccommodationUnit::query()
            ->with('type')
            ->where('is_active', true)
            ->where('capacity', '>=', $guests)
            ->whereNotIn('status', ['maintenance', 'cleaning', 'inactive'])
            ->whereDoesntHave('reservations', fn ($query) => $query
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->where('check_in', '<', $checkOut->toDateString())
                ->where('check_out', '>', $checkIn->toDateString()))
            ->whereDoesntHave('blocks', fn ($query) => $query
                ->whereNull('released_at')
                ->where('starts_on', '<', $checkOut->toDateString())
                ->where('ends_on', '>', $checkIn->toDateString()))
            ->get()
            ->map(function (AccommodationUnit $unit) use ($checkIn, $checkOut, $nights) {
                $rate = AccommodationRate::query()
                    ->where('accommodation_type_id', $unit->accommodation_type_id)
                    ->where('is_active', true)
                    ->whereDate('starts_on', '<=', $checkIn->toDateString())
                    ->whereDate('ends_on', '>=', $checkOut->subDay()->toDateString())
                    ->where('minimum_nights', '<=', $nights)
                    ->orderByDesc('starts_on')
                    ->first();

                if (! $rate || ! $this->coversWeekdays($rate->weekdays, $checkIn, $checkOut)) {
                    return null;
                }

                $unit->setAttribute('quote_nights', $nights);
                $unit->setAttribute('quote_nightly_rate', (float) $rate->nightly_rate);
                $unit->setAttribute('quote_total', round($nights * (float) $rate->nightly_rate, 2));

                return $unit;
            })
            ->filter()
            ->values();
    }

    private function coversWeekdays(?array $weekdays, CarbonImmutable $checkIn, CarbonImmutable $checkOut): bool
    {
        if (! $weekdays) {
            return true;
        }

        for ($date = $checkIn; $date->lt($checkOut); $date = $date->addDay()) {
            if (! in_array($date->dayOfWeekIso, array_map('intval', $weekdays), true)) {
                return false;
            }
        }

        return true;
    }
}
