<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseMaintenanceService
{
    public function databaseName(): string
    {
        return (string) config('database.connections.mysql.database');
    }

    public function assertSafeTarget(): void
    {
        $allowed = (string) config('maintenance.allowed_database');
        $database = $this->databaseName();

        if (!config('maintenance.enabled')) {
            throw new RuntimeException('Las operaciones de mantenimiento están deshabilitadas.');
        }

        if (config('database.default') !== 'mysql' || $allowed === '' || !hash_equals($allowed, $database)) {
            throw new RuntimeException('La base de datos activa no coincide con el destino autorizado.');
        }
    }

    public function createBackup(): string
    {
        $this->assertSafeTarget();
        $connection = config('database.connections.mysql');
        $directory = storage_path('app/private/backups');

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el directorio privado de respaldos.');
        }

        $path = $directory.DIRECTORY_SEPARATOR.'backup_'.now()->format('Y-m-d_H-i-s').'_'.bin2hex(random_bytes(4)).'.sql';
        $process = new Process([
            (string) config('maintenance.mysqldump_binary'),
            '--host='.(string) $connection['host'],
            '--port='.(string) $connection['port'],
            '--user='.(string) $connection['username'],
            '--single-transaction',
            '--routines',
            '--triggers',
            '--result-file='.$path,
            $this->databaseName(),
        ], null, $this->processEnvironment((string) $connection['password']));
        $process->setTimeout(config('maintenance.timeout'));
        $process->run();

        if (!$process->isSuccessful() || !is_file($path) || filesize($path) === 0) {
            @unlink($path);
            throw new RuntimeException('No se pudo generar una copia de seguridad válida.');
        }

        return $path;
    }

    public function restore(string $path): void
    {
        $this->assertSafeTarget();
        $this->assertSqlBackup($path);
        $connection = config('database.connections.mysql');
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException('No se pudo leer el respaldo recibido.');
        }

        try {
            $process = new Process([
                (string) config('maintenance.mysql_binary'),
                '--host='.(string) $connection['host'],
                '--port='.(string) $connection['port'],
                '--user='.(string) $connection['username'],
                '--database='.$this->databaseName(),
            ], null, $this->processEnvironment((string) $connection['password']));
            $process->setInput($stream);
            $process->setTimeout(config('maintenance.timeout'));
            $process->run();
        } finally {
            fclose($stream);
        }

        if (!$process->isSuccessful()) {
            throw new RuntimeException('La herramienta de base de datos rechazó la restauración.');
        }
    }

    public function assertSqlBackup(string $path): void
    {
        if (!is_file($path) || filesize($path) === 0) {
            throw new RuntimeException('El archivo de respaldo está vacío o no existe.');
        }

        $sample = file_get_contents($path, false, null, 0, 16384);

        if ($sample === false || str_contains($sample, "\0")) {
            throw new RuntimeException('El respaldo no es un archivo SQL de texto válido.');
        }

        $looksLikeSql = str_contains($sample, '-- MySQL dump')
            || preg_match('/\b(CREATE TABLE|INSERT INTO|SET FOREIGN_KEY_CHECKS)\b/i', $sample) === 1;

        if (!$looksLikeSql) {
            throw new RuntimeException('El archivo no contiene una cabecera SQL reconocible.');
        }
    }

    private function processEnvironment(string $password): ?array
    {
        return $password === '' ? null : ['MYSQL_PWD' => $password];
    }
}
