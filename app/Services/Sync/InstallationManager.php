<?php

namespace App\Services\Sync;

use App\Models\Installation;
use Illuminate\Support\Str;
use RuntimeException;

class InstallationManager
{
    public function current(): Installation
    {
        $uuid = config('sync.installation_uuid');
        if (! $uuid || ! Str::isUuid($uuid)) {
            throw new RuntimeException('INSTALLATION_UUID no está configurado correctamente.');
        }

        return Installation::firstOrCreate(['uuid' => $uuid], [
            'name' => config('app.name'), 'mode' => config('sync.mode'),
            'device_uuid' => config('sync.device_uuid') ?: (string) Str::uuid(),
            'sync_status' => config('sync.enabled') ? 'idle' : 'disabled',
        ]);
    }
}
