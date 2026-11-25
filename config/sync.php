<?php

return [
    'mode' => env('DATA_MODE', 'cloud'),
    'enabled' => env('SYNC_ENABLED', false),
    'installation_uuid' => env('INSTALLATION_UUID'),
    'device_uuid' => env('DEVICE_UUID'),
    'endpoint' => env('SYNC_ENDPOINT'),
    'token' => env('SYNC_TOKEN'),
    'timeout' => (int) env('SYNC_TIMEOUT', 10),
    'batch_size' => (int) env('SYNC_BATCH_SIZE', 100),
    'backup_disk' => env('CLOUD_BACKUP_DISK', 'local'),
    'backup_retention_days' => (int) env('CLOUD_BACKUP_RETENTION_DAYS', 30),
];
