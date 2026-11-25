<?php

namespace App\Services\Sync;

use App\Models\SyncOutbox;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SyncPushService
{
    public function __construct(private readonly InstallationManager $installations) {}

    public function push(): int
    {
        if (! config('sync.enabled')) {
            return 0;
        }
        if (! config('sync.endpoint') || ! config('sync.token')) {
            throw new RuntimeException('La sincronización no tiene endpoint o token configurado.');
        }
        $events = SyncOutbox::whereIn('status', ['pending', 'failed'])->where('available_at', '<=', now())->orderBy('id')->limit(config('sync.batch_size'))->get();
        if ($events->isEmpty()) {
            return 0;
        }

        try {
            $response = Http::timeout(config('sync.timeout'))->withToken(config('sync.token'))->acceptJson()->post(rtrim(config('sync.endpoint'), '/').'/api/sync/events', ['events' => $events->map->only(['event_uuid', 'installation_uuid', 'aggregate_type', 'aggregate_id', 'aggregate_version', 'event_type', 'payload', 'checksum'])->all()]);
            $response->throw();
            $acked = $response->json('acked', []);
            SyncOutbox::whereIn('event_uuid', $acked)->update(['status' => 'acked', 'acked_at' => now(), 'last_error' => null]);
            $this->installations->current()->update(['sync_status' => 'online', 'last_synced_at' => now(), 'last_error' => null]);

            return count($acked);
        } catch (Throwable $exception) {
            foreach ($events as $event) {
                $event->update(['status' => 'failed', 'attempts' => $event->attempts + 1, 'available_at' => now()->addSeconds(min(3600, 2 ** min(10, $event->attempts + 1))), 'last_error' => Str($exception->getMessage())->limit(500)]);
            }
            $this->installations->current()->update(['sync_status' => 'offline', 'last_error' => Str($exception->getMessage())->limit(500)]);
            throw $exception;
        }
    }
}
