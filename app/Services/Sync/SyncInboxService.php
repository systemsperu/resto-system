<?php

namespace App\Services\Sync;

use App\Models\Reservation;
use App\Models\SyncConflict;
use App\Models\SyncInbox;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class SyncInboxService
{
    public function receive(array $event): SyncInbox
    {
        $data = Validator::make($event, ['event_uuid' => ['required', 'uuid'], 'installation_uuid' => ['required', 'uuid'], 'event_type' => ['required', 'in:reservation.command.create'], 'payload' => ['required', 'array'], 'checksum' => ['required', 'size:64']])->validate();
        $json = json_encode($data['payload'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if (! hash_equals($data['checksum'], hash('sha256', $json))) {
            throw new RuntimeException('El checksum del evento no coincide.');
        }

        return DB::transaction(function () use ($data): SyncInbox {
            $inbox = SyncInbox::where('event_uuid', $data['event_uuid'])->lockForUpdate()->first();
            if ($inbox) {
                return $inbox;
            }
            $inbox = SyncInbox::create(['event_uuid' => $data['event_uuid'], 'source_installation_uuid' => $data['installation_uuid'], 'event_type' => $data['event_type'], 'payload' => $data['payload'], 'checksum' => $data['checksum'], 'status' => 'received']);
            $this->applyReservation($inbox);

            return $inbox->refresh();
        });
    }

    private function applyReservation(SyncInbox $inbox): void
    {
        $payload = Validator::make($inbox->payload, ['client_name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'reservation_time' => ['required', 'date'], 'people' => ['required', 'integer', 'min:1', 'max:100'], 'table_id' => ['nullable', 'integer', 'exists:tables,id'], 'note' => ['nullable', 'string', 'max:1000']])->validate();
        $time = Carbon::parse($payload['reservation_time']);
        $collision = ! empty($payload['table_id']) && Reservation::where('table_id', $payload['table_id'])->whereIn('status', ['pending', 'confirmed'])->whereBetween('reservation_time', [$time->copy()->subHours(2), $time->copy()->addHours(2)])->exists();
        if ($collision) {
            SyncConflict::create(['conflict_uuid' => (string) Str::uuid(), 'event_uuid' => $inbox->event_uuid, 'aggregate_type' => 'Reservation', 'aggregate_id' => $inbox->event_uuid, 'remote_payload' => $payload, 'reason' => 'La mesa ya tiene una reserva cercana.', 'status' => 'open']);
            $inbox->update(['status' => 'conflict', 'processed_at' => now()]);

            return;
        }
        Reservation::withoutEvents(fn () => Reservation::create($payload + ['status' => 'pending']));
        $inbox->update(['status' => 'processed', 'processed_at' => now()]);
    }
}
