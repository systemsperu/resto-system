<?php

namespace Tests\Feature;

use App\Models\DeliveryDriver;
use App\Models\Setting;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_desactiva_modulo_sin_eliminar_datos_y_queda_auditado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driver = DeliveryDriver::create(['name' => 'Repartidor', 'phone' => '999999999', 'is_active' => true]);

        $this->actingAs($admin)
            ->from(route('modules.index'))
            ->patch(route('modules.update', 'delivery'), ['enabled' => false])
            ->assertRedirect(route('modules.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse(app(ModuleManager::class)->isEnabled('delivery'));
        $this->assertDatabaseHas('delivery_drivers', ['id' => $driver->id]);
        $this->assertDatabaseHas('system_audits', [
            'user_id' => $admin->id,
            'action' => 'module.updated',
            'target' => 'delivery',
            'status' => 'success',
        ]);

        $stored = json_decode(Setting::query()->where('key', 'enabled_modules')->value('value'), true);
        $this->assertIsArray($stored);
        $this->assertNotContains('delivery', $stored);
    }

    public function test_modulo_desactivado_oculta_sus_rutas_con_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        app(ModuleManager::class)->setEnabled('delivery', false);

        $this->actingAs($admin)
            ->get(route('delivery.index'))
            ->assertNotFound();
    }

    public function test_usuario_sin_permiso_no_administra_modulos(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->patch(route('modules.update', 'delivery'), ['enabled' => false])
            ->assertForbidden();

        $this->assertTrue(app(ModuleManager::class)->isEnabled('delivery'));
    }

    public function test_no_desactiva_dependencia_requerida_por_modulos_activos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('modules.index'))
            ->patch(route('modules.update', 'restaurant'), ['enabled' => false])
            ->assertRedirect(route('modules.index'))
            ->assertSessionHasErrors('module');

        $this->assertTrue(app(ModuleManager::class)->isEnabled('restaurant'));
    }

    public function test_valida_modulo_y_estado_solicitados(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('modules.index'))
            ->patch(route('modules.update', 'desconocido'), ['enabled' => 'quizas'])
            ->assertRedirect(route('modules.index'))
            ->assertSessionHasErrors(['module', 'enabled']);

        $this->assertDatabaseMissing('settings', ['key' => 'enabled_modules']);
    }
}
