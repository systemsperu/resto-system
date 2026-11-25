<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Sunat\SunatConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecretSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_credenciales_se_cifran_y_no_regresan_al_formulario(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('sunat_sol_user', 'usuario-secreto-prueba');
        Setting::put('sunat_sol_pass', 'clave-secreta-prueba');

        $stored = DB::table('settings')->where('key', 'sunat_sol_pass')->first();

        $this->assertTrue((bool) $stored->is_secret);
        $this->assertNotSame('clave-secreta-prueba', $stored->value);
        $this->assertSame('clave-secreta-prueba', (new SunatConfig())->solPass());

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertDontSee('usuario-secreto-prueba')
            ->assertDontSee('clave-secreta-prueba');
    }

    public function test_un_campo_secreto_vacio_conserva_el_valor_existente(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::put('sunat_sol_pass', 'clave-existente');
        $before = DB::table('settings')->where('key', 'sunat_sol_pass')->value('value');

        $this->actingAs($admin)
            ->post(route('settings.update'), $this->validPayload([
                'sunat_sol_pass' => '',
                'sunat_sol_user' => '',
                'sunat_cert_password' => '',
            ]))
            ->assertRedirect();

        $after = DB::table('settings')->where('key', 'sunat_sol_pass')->value('value');
        $this->assertSame($before, $after);
        $this->assertSame('clave-existente', (new SunatConfig())->solPass());
    }

    public function test_no_permite_seleccionar_produccion_mientras_el_transporte_esta_bloqueado(): void
    {
        config(['sunat.send_enabled' => false]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('settings.index'))
            ->post(route('settings.update'), $this->validPayload([
                'sunat_environment' => 'produccion',
            ]))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors('sunat_environment');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Negocio de prueba',
            'company_phone' => '',
            'company_address' => '',
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
