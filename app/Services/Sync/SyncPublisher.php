<?php

namespace App\Services\Sync;

use App\Models\SyncOutbox;
use Illuminate\Support\Str;

class SyncPublisher
{
    public function __construct(private readonly InstallationManager $installations) {}

    public function publish(string $type, string|int $id, string $event, array $payload, ?int $version = null): SyncOutbox
    {
        $installation = $this->installations->current();
        $clean = $this->withoutSecrets($payload);
        $version ??= (int) (microtime(true) * 1000000);
        $json = json_encode($clean, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return SyncOutbox::firstOrCreate(
            ['aggregate_type' => $type, 'aggregate_id' => (string) $id, 'aggregate_version' => $version],
            ['event_uuid' => (string) Str::uuid(), 'installation_uuid' => $installation->uuid, 'event_type' => $event, 'payload' => $clean, 'checksum' => hash('sha256', $json), 'status' => 'pending', 'available_at' => now()]
        );
    }

    private function withoutSecrets(array $payload): array
    {
        $blocked = ['password', 'token', 'secret', 'certificate', 'session', 'sunat_sol_pass', 'sunat_cert_password'];

        return collect($payload)->reject(fn ($value, $key) => collect($blocked)->contains(fn ($term) => str_contains(strtolower((string) $key), $term)))->map(fn ($value) => is_array($value) ? $this->withoutSecrets($value) : $value)->all();
    }
}
