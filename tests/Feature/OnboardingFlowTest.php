<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_instalacion_nueva_muestra_el_primer_paso_al_administrador(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('onboarding.show'))
            ->assertOk()
            ->assertViewIs('onboarding.show')
            ->assertViewHas('currentStep', 1)
            ->assertViewHas('onboarding', fn (array $state): bool => $state['status'] === 'pending'
                && $state['completed_steps'] === []
                && $state['legacy_installation'] === false
            );
    }

    public function test_una_instalacion_legacy_con_nombre_existente_no_es_forzada_al_asistente(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('company_name', 'Negocio existente');

        $this->actingAs($admin)
            ->get(route('onboarding.show'))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('settings', ['key' => 'setup_status']);
    }

    public function test_un_usuario_sin_permiso_no_puede_ver_ni_guardar_el_onboarding(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);

        $this->actingAs($waiter)
            ->get(route('onboarding.show'))
            ->assertForbidden();

        $this->actingAs($waiter)
            ->post(route('onboarding.store'), ['step' => 1])
            ->assertForbidden();
    }

    public function test_rechaza_pasos_fuera_del_rango_y_no_modifica_el_progreso(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.store'), ['step' => 6])
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors('step');

        $this->assertDatabaseMissing('settings', ['key' => 'setup_status']);
        $this->assertDatabaseMissing('settings', ['key' => 'setup_completed_steps']);
    }

    public function test_reanuda_en_el_primer_paso_incompleto_y_conserva_los_datos_guardados(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('onboarding.store'), [
                'step' => 1,
                'business_name' => 'Recreo Bosque',
                'business_type' => 'tourist_recreation',
            ])
            ->assertRedirect(route('onboarding.show', ['step' => 2]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->get(route('onboarding.show'))
            ->assertOk()
            ->assertViewHas('currentStep', 2)
            ->assertViewHas('onboarding', fn (array $state): bool => $state['completed_steps'] === [1]
                && $state['values']['business_name'] === 'Recreo Bosque'
            );
    }

    public function test_la_validacion_no_guarda_un_paso_invalido(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.store'), [
                'step' => 1,
                'business_name' => 'X',
                'tax_id' => '123',
                'business_type' => 'valor-invalido',
            ])
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors(['business_name', 'tax_id', 'business_type']);

        $this->assertDatabaseMissing('settings', ['key' => 'company_name']);
        $this->assertDatabaseMissing('settings', ['key' => 'setup_completed_steps']);
    }

    public function test_las_mutaciones_del_onboarding_pertenecen_al_grupo_web_con_csrf(): void
    {
        foreach (['onboarding.store', 'onboarding.exit'] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertSame(['POST'], $route->methods());
            $this->assertContains('web', $route->gatherMiddleware());
        }
    }

    public function test_finaliza_sin_configuracion_sunat_y_registra_la_auditoria(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('setup_status', 'in_progress');
        Setting::put('setup_completed_steps', '[1,2,3,4]');
        Setting::put('setup_current_step', '5');

        $this->actingAs($admin)
            ->post(route('onboarding.store'), ['step' => 5, 'confirmed' => '1'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['key' => 'setup_status', 'value' => 'completed']);
        $this->assertDatabaseHas('settings', ['key' => 'setup_completed_by', 'value' => (string) $admin->id]);
        $this->assertDatabaseHas('system_audits', [
            'user_id' => $admin->id,
            'action' => 'onboarding.completed',
            'target' => 'installation',
            'status' => 'success',
        ]);
        $this->assertSame(0, Setting::query()->where('key', 'like', 'sunat_%')->count());

        $this->actingAs($admin)
            ->get(route('onboarding.show'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_no_finaliza_si_faltan_pasos_o_confirmacion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('setup_status', 'in_progress');
        Setting::put('setup_completed_steps', '[1,2,3]');

        $this->actingAs($admin)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.store'), ['step' => 5, 'confirmed' => '1'])
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors('step');

        $this->actingAs($admin)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.store'), ['step' => 5])
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors('confirmed');

        $this->assertDatabaseMissing('settings', ['key' => 'setup_completed_at']);
        $this->assertDatabaseMissing('system_audits', ['action' => 'onboarding.completed']);
    }

    public function test_instalacion_explicitamente_pendiente_bloquea_operacion_hasta_completarla(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $waiter = User::factory()->create(['role' => 'waiter']);
        Setting::put('setup_status', 'pending');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding.show'));

        $this->actingAs($waiter)
            ->get(route('pos.index'))
            ->assertServiceUnavailable();
    }

    public function test_instalacion_legacy_sin_estado_conserva_acceso_operativo(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('company_name', 'Negocio existente');

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk();
    }
}
