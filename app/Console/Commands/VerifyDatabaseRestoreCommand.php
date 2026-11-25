<?php

namespace App\Console\Commands;

use App\Services\DatabaseMaintenanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class VerifyDatabaseRestoreCommand extends Command
{
    protected $signature = 'maintenance:verify-restore {--confirm= : Escriba VALIDAR para ejecutar}';

    protected $description = 'Verifica un respaldo en una base temporal aislada y la elimina al terminar';

    public function handle(DatabaseMaintenanceService $maintenance): int
    {
        if ($this->option('confirm') !== 'VALIDAR') {
            $this->error('Confirmación inválida. No se creó ninguna base temporal.');

            return self::FAILURE;
        }

        $maintenance->assertSafeTarget();
        $sourceDatabase = $maintenance->databaseName();
        $temporaryDatabase = 'recreosuite_restore_check_'.now()->format('YmdHis').'_'.bin2hex(random_bytes(2));

        if (!preg_match('/^recreosuite_restore_check_[a-zA-Z0-9_]+$/', $temporaryDatabase)) {
            $this->error('El nombre temporal no superó la validación de seguridad.');

            return self::FAILURE;
        }

        $backup = $maintenance->createBackup();
        $created = false;

        try {
            DB::connection('mysql')->statement("CREATE DATABASE `{$temporaryDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $created = true;

            config([
                'database.connections.mysql.database' => $temporaryDatabase,
                'maintenance.allowed_database' => $temporaryDatabase,
            ]);
            DB::purge('mysql');

            $maintenance->restore($backup);

            $requiredTables = ['migrations', 'users', 'settings', 'orders', 'order_details'];
            foreach ($requiredTables as $table) {
                if (!Schema::connection('mysql')->hasTable($table)) {
                    throw new \RuntimeException("La tabla requerida {$table} no fue restaurada.");
                }
            }

            $this->info('Restauración temporal verificada correctamente.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('La restauración temporal no superó la verificación.');

            return self::FAILURE;
        } finally {
            config([
                'database.connections.mysql.database' => $sourceDatabase,
                'maintenance.allowed_database' => $sourceDatabase,
            ]);
            DB::purge('mysql');

            if ($created) {
                DB::connection('mysql')->statement("DROP DATABASE `{$temporaryDatabase}`");
            }

            @unlink($backup);
        }
    }
}
