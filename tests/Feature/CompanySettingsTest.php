<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Settings\CompanySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_actualiza_y_recupera_un_perfil_tipado(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('settings.update'), $this->payload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $profile = app(CompanySettings::class)->get();

        $this->assertSame('Recreo de Prueba', $profile->name);
        $this->assertSame('contacto@example.test', $profile->email);
        $this->assertSame('PE', $profile->countryCode);
        $this->assertSame('Satipo', $profile->province);
        $this->assertSame('America/Lima', $profile->timezone);
        $this->assertNull($profile->phone);
    }

    public function test_usuario_sin_permiso_no_puede_actualizar_la_empresa(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->post(route('settings.update'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', [
            'key' => 'company_name',
            'value' => 'Recreo de Prueba',
        ]);
    }

    public function test_rechaza_datos_invalidos_sin_persistirlos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('settings.index'))
            ->post(route('settings.update'), $this->payload([
                'company_email' => 'correo-invalido',
                'company_country_code' => 'PER',
                'company_ubigeo' => '123',
                'timezone' => 'Zona/Inexistente',
            ]))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors([
                'company_email', 'company_country_code', 'company_ubigeo', 'timezone',
            ]);

        $this->assertSame(0, Setting::query()->count());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => ' Recreo de Prueba ',
            'company_phone' => '',
            'company_email' => 'contacto@example.test',
            'company_website' => 'https://example.test',
            'company_address' => 'Jr. Principal 123',
            'company_country_code' => 'pe',
            'company_department' => 'Junín',
            'company_province' => 'Satipo',
            'company_district' => 'Satipo',
            'company_ubigeo' => '120601',
            'timezone' => 'America/Lima',
            'currency_symbol' => 'S/',
            'ticket_footer' => 'Gracias',
            'sunat_ruc' => '20000000001',
            'sunat_razon_social' => 'EMPRESA PRUEBA SAC',
            'sunat_nombre_comercial' => 'PRUEBA',
            'sunat_direccion_fiscal' => 'Dirección de prueba',
            'sunat_ubigeo' => '150101',
            'sunat_departamento' => 'LIMA',
            'sunat_provincia' => 'LIMA',
            'sunat_distrito' => 'LIMA',
            'sunat_urbanizacion' => '-',
            'sunat_environment' => 'beta',
            'sunat_igv_rate' => '18',
        ], $overrides);
    }
}
