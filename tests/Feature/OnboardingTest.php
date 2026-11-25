<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Onboarding\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_solo_administrador_puede_guardar_el_asistente(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->post(route('onboarding.store'), $this->stepOne())
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', ['key' => 'setup_status']);
    }

    public function test_instalacion_existente_sin_estado_se_considera_completa_sin_modificarla(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('company_name', 'Negocio ya operativo');

        $state = app(OnboardingService::class)->state();

        $this->assertTrue($state->isCompleted());
        $this->assertTrue($state->legacyInstallation);
        $this->assertDatabaseMissing('settings', ['key' => 'setup_status']);

        $this->actingAs($admin)
            ->get(route('onboarding.show'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_guarda_progreso_y_reanuda_en_el_primer_paso_pendiente(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('setup_status', 'pending');

        $this->actingAs($admin)
            ->post(route('onboarding.store'), $this->stepOne())
            ->assertRedirect(route('onboarding.show', ['step' => 2]));

        $state = app(OnboardingService::class)->state();
        $this->assertSame('in_progress', $state->status);
        $this->assertSame([1], $state->completedSteps);
        $this->assertSame(2, $state->currentStep);
        $this->assertSame('Recreo Satipo', $state->values['business_name']);
        $this->assertNotNull(Setting::query()->where('key', 'setup_started_at')->value('value'));
    }

    public function test_un_paso_invalido_no_avanza_ni_borra_progreso(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('setup_status', 'pending');

        $this->actingAs($admin)->post(route('onboarding.store'), $this->stepOne());

        $this->actingAs($admin)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.store'), [
                'step' => 2,
                'brand_primary_color' => '#FFFFFF',
            ])
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors('brand_primary_color');

        $this->assertSame([1], app(OnboardingService::class)->state()->completedSteps);
    }

    public function test_completa_los_cinco_pasos_y_registra_auditoria(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('setup_status', 'pending');

        $this->actingAs($admin)->post(route('onboarding.store'), $this->stepOne());
        $this->actingAs($admin)->post(route('onboarding.store'), [
            'step' => 2,
            'brand_primary_color' => '#1F2937',
            'brand_secondary_color' => '#F59E0B',
        ]);
        $this->actingAs($admin)->post(route('onboarding.store'), [
            'step' => 3,
            'contact_phone' => '+51 999 888 777',
            'contact_whatsapp' => '+51999888777',
            'contact_email' => 'contacto@example.test',
            'address_line' => 'Jr. Principal 123',
            'address_department' => 'Junín',
            'address_province' => 'Satipo',
            'address_district' => 'Satipo',
            'address_ubigeo' => '120601',
        ]);
        $this->actingAs($admin)->post(route('onboarding.store'), [
            'step' => 4,
            'country_code' => 'PE',
            'locale' => 'es-PE',
            'timezone' => 'America/Lima',
            'currency_code' => 'PEN',
            'date_format' => 'dd/MM/yyyy',
            'week_starts_on' => 1,
            'ticket_footer' => 'Gracias por su visita',
        ]);

        $this->actingAs($admin)
            ->post(route('onboarding.store'), ['step' => 5, 'confirmed' => '1'])
            ->assertRedirect(route('dashboard'));

        $state = app(OnboardingService::class)->state();
        $this->assertTrue($state->isCompleted());
        $this->assertSame(5, $state->currentStep);
        $this->assertDatabaseHas('settings', ['key' => 'setup_completed_by', 'value' => (string) $admin->id]);
        $this->assertDatabaseHas('system_audits', [
            'user_id' => $admin->id,
            'action' => 'onboarding.completed',
            'target' => 'installation',
            'status' => 'success',
        ]);
    }

    public function test_no_completa_si_faltan_pasos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('setup_status', 'pending');

        $this->actingAs($admin)
            ->from(route('onboarding.show'))
            ->post(route('onboarding.store'), ['step' => 5, 'confirmed' => '1'])
            ->assertRedirect(route('onboarding.show'))
            ->assertSessionHasErrors('step');

        $this->assertDatabaseMissing('system_audits', ['action' => 'onboarding.completed']);
    }

    private function stepOne(): array
    {
        return [
            'step' => 1,
            'business_name' => 'Recreo Satipo',
            'legal_name' => 'Recreo Satipo SAC',
            'tax_id' => '20123456789',
            'business_tagline' => 'Naturaleza y sabor',
            'business_type' => 'tourist_recreation',
        ];
    }
}
