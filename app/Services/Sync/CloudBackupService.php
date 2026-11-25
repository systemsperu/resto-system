<?php

namespace App\Services\Sync;

use App\Models\CloudBackup;
use App\Services\DatabaseMaintenanceService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CloudBackupService
{
    public function __construct(private readonly DatabaseMaintenanceService $maintenance) {}

    public function create(): CloudBackup
    {
        $plainPath = $this->maintenance->createBackup();
        try {
            $plain = file_get_contents($plainPath);
            if ($plain === false) {
                throw new RuntimeException('No se pudo leer el respaldo local.');
            }
            $checksum = hash('sha256', $plain);
            $encrypted = Crypt::encryptString($plain);
            $disk = (string) config('sync.backup_disk');
            $path = 'backups/'.config('sync.installation_uuid').'/'.now()->format('Y/m').'/'.Str::uuid().'.sql.enc';
            if (! Storage::disk($disk)->put($path, $encrypted)) {
                throw new RuntimeException('No se pudo guardar el respaldo cifrado.');
            }
            $verified = hash_equals($checksum, hash('sha256', Crypt::decryptString(Storage::disk($disk)->get($path))));
            $backup = CloudBackup::create(['backup_uuid' => (string) Str::uuid(), 'disk' => $disk, 'path' => $path, 'checksum' => $checksum, 'size_bytes' => strlen($encrypted), 'encrypted' => true, 'verified' => $verified, 'verified_at' => $verified ? now() : null, 'status' => $verified ? 'verified' : 'failed']);
            CloudBackup::where('created_at', '<', now()->subDays(config('sync.backup_retention_days')))->get()->each(function (CloudBackup $expired): void {
                Storage::disk($expired->disk)->delete($expired->path);
                $expired->delete();
            });

            return $backup;
        } finally {
            if (is_file($plainPath)) {
                unlink($plainPath);
            }
        }
    }
}
