<?php

namespace Tests\Feature;

use App\Branding\BrandSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_colores_accesibles_y_recursos_de_marca_compatibles(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('settings.update'), $this->payload([
                'brand_primary_color' => '#1f2937',
                'brand_secondary_color' => '#f59e0b',
                'company_logo' => UploadedFile::fake()->image('logo.png', 800, 400),
                'brand_icon' => UploadedFile::fake()->image('icon.png', 256, 256),
                'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $brand = app(BrandSettings::class)->get();

        $this->assertSame('Recreo de Prueba', $brand->companyName);
        $this->assertSame('#1F2937', $brand->primaryColor);
        $this->assertSame('#F59E0B', $brand->secondaryColor);
        $this->assertNotNull($brand->logoPath);
        Storage::disk('public')->assertExists($brand->logoPath);
        Storage::disk('public')->assertExists($brand->iconPath);
        Storage::disk('public')->assertExists($brand->faviconPath);
    }

    public function test_rechaza_colores_sin_contraste_aa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('settings.index'))
            ->post(route('settings.update'), $this->payload([
                'brand_primary_color' => '#FFFFFF',
                'brand_secondary_color' => '#000000',
            ]))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors(['brand_primary_color', 'brand_secondary_color']);

        $this->assertDatabaseMissing('settings', ['key' => 'brand_primary_color']);
    }

    public function test_reemplaza_y_retira_recursos_administrados(): void
    {
        Storage::fake('public');
        $service = app(BrandSettings::class);

        $service->updateResources([
            'company_logo' => UploadedFile::fake()->image('anterior.png'),
        ]);
        $oldPath = $service->get()->logoPath;

        $service->updateResources([
            'company_logo' => UploadedFile::fake()->image('nuevo.png'),
        ]);
        $newPath = $service->get()->logoPath;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $service->updateResources([], ['company_logo' => true]);

        $this->assertNull($service->get()->logoPath);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_no_elimina_archivos_fuera_de_directorios_administrados(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('externos/logo.png', 'contenido');
        Setting::put('company_logo', 'externos/logo.png');

        app(BrandSettings::class)->updateResources([], ['company_logo' => true]);

        Storage::disk('public')->assertExists('externos/logo.png');
        $this->assertNull(app(BrandSettings::class)->get()->logoPath);
    }

    public function test_contexto_descarta_colores_y_rutas_manipulados_fuera_del_formulario(): void
    {
        Setting::put('brand_primary_color', 'red; background:url(javascript:alert(1))');
        Setting::put('brand_secondary_color', '#000000');
        Setting::put('company_logo', '../private/certificado.pfx');

        $brand = app(BrandSettings::class)->get();

        $this->assertSame('#1F2937', $brand->primaryColor);
        $this->assertSame('#F59E0B', $brand->secondaryColor);
        $this->assertNull($brand->logoPath);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Recreo de Prueba',
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
