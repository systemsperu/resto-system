<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SystemMaintenanceSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_requiere_frase_exacta_para_generar_un_respaldo(): void
    {
        config(['maintenance.enabled' => false]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('system.index'))
            ->post(route('system.backup'), [
                'password' => 'password',
                'confirmation' => 'RESPALDAR otra_base',
            ])
            ->assertRedirect(route('system.index'))
            ->assertSessionHasErrors('confirmation');
    }

    public function test_mantenimiento_deshabilitado_falla_sin_ejecutar_herramientas_externas(): void
    {
        config(['maintenance.enabled' => false]);
        $admin = User::factory()->create(['role' => 'admin']);
        $database = config('database.connections.mysql.database');

        $this->actingAs($admin)
            ->from(route('system.index'))
            ->post(route('system.backup'), [
                'password' => 'password',
                'confirmation' => 'RESPALDAR '.$database,
            ])
            ->assertRedirect(route('system.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('system_audits', [
            'user_id' => $admin->id,
            'action' => 'backup',
            'target' => $database,
            'status' => 'failed',
        ]);
    }

    public function test_rechaza_un_archivo_que_no_tiene_contenido_sql_reconocible(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'recreosuite-invalid-');
        file_put_contents($path, 'contenido que no corresponde a una copia SQL');

        try {
            $this->expectException(RuntimeException::class);
            app(DatabaseMaintenanceService::class)->assertSqlBackup($path);
        } finally {
            @unlink($path);
        }
    }

    public function test_acepta_la_estructura_de_un_dump_mysql_sin_ejecutarlo(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'recreosuite-valid-');
        file_put_contents($path, "-- MySQL dump\nCREATE TABLE `example` (`id` bigint);\n");

        try {
            app(DatabaseMaintenanceService::class)->assertSqlBackup($path);
            $this->addToAssertionCount(1);
        } finally {
            @unlink($path);
        }
    }
}
