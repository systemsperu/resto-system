<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_ve_la_jerarquia_operativa_solicitada(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $modules = app(ModuleManager::class);
        $modules->setEnabled('landing', true);
        $modules->setEnabled('marketing', true);
        $modules->setEnabled('lodging', true);

        $this->actingAs($admin)->get(route('settings.index'))
            ->assertOk()
            ->assertSee('POS RES')
            ->assertSee('Historial de ventas')
            ->assertSee('Resumen diario de BV')
            ->assertSee('Solicitudes QR')
            ->assertSee('Hospedaje')
            ->assertSee('Facturación electrónica')
            ->assertSee('Caja / Arqueo')
            ->assertSee('Cocina (KDS)')
            ->assertSee('Gestión')
            ->assertSee('Carta digital')
            ->assertSee('Personal / Usuarios')
            ->assertSee('Analítica web');
    }

    public function test_cocina_solo_ve_su_acceso_operativo(): void
    {
        $user = User::factory()->create(['role' => 'kitchen']);

        $this->actingAs($user)->get(route('kitchen.index'))
            ->assertOk()
            ->assertSee('Cocina (KDS)')
            ->assertDontSee('POS RES')
            ->assertDontSee('Facturación electrónica')
            ->assertDontSee('Configuración');
    }
}
