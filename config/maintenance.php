<?php

return [
    'enabled' => env('SYSTEM_MAINTENANCE_ENABLED', false),
    'allowed_database' => env('SYSTEM_MAINTENANCE_DATABASE'),
    'mysqldump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
    'mysql_binary' => env('MYSQL_BINARY', 'mysql'),
    'timeout' => (int) env('SYSTEM_MAINTENANCE_TIMEOUT', 300),
    'max_restore_kb' => (int) env('SYSTEM_RESTORE_MAX_KB', 51200),
];
