<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_no_autenticado_no_accede_a_la_operacion_interna(): void
    {
        $this->get(route('kitchen.index'))
            ->assertRedirect(route('login'));
    }

    public function test_un_mozo_no_accede_a_la_zona_administrativa(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);

        $this->actingAs($waiter)
            ->get(route('settings.index'))
            ->assertForbidden();
    }

    public function test_un_cajero_no_accede_a_la_zona_exclusiva_del_administrador(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_un_administrador_accede_a_la_configuracion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk();
    }
}
