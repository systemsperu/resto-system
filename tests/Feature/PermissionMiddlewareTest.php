<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_mozo_no_puede_administrar_caja(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);

        $this->actingAs($waiter)
            ->get(route('cash_registers.create'))
            ->assertForbidden();
    }

    public function test_un_usuario_de_cocina_solo_ingresa_a_su_area_operativa(): void
    {
        $kitchen = User::factory()->create(['role' => 'kitchen']);

        $this->actingAs($kitchen)
            ->get(route('kitchen.index'))
            ->assertOk();

        $this->actingAs($kitchen)
            ->get(route('pos.index'))
            ->assertForbidden();
    }

    public function test_un_cajero_puede_ver_cocina_pero_no_cambiar_estados(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->assertTrue($cashier->hasPermission('kitchen.view'));
        $this->assertFalse($cashier->hasPermission('kitchen.manage'));
    }

    public function test_solo_el_administrador_tiene_capacidades_destructivas(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->assertTrue($admin->hasPermission('reservations.delete'));
        $this->assertTrue($admin->hasPermission('delivery.drivers.manage'));
        $this->assertFalse($cashier->hasPermission('reservations.delete'));
        $this->assertFalse($cashier->hasPermission('delivery.drivers.manage'));
    }
}
