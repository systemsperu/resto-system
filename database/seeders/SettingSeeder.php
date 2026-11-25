<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $isNewInstallation = ! Setting::query()->exists();

        $data = [
            // Datos básicos
            'company_name' => 'Mi Restaurante VIP',
            'company_address' => 'Av. Gastronómica 123, Lima',
            'company_phone' => '(01) 555-9999',
            'company_email' => '',
            'company_website' => '',
            'company_country_code' => 'PE',
            'company_department' => 'Lima',
            'company_province' => 'Lima',
            'company_district' => 'Lima',
            'company_ubigeo' => '150101',
            'timezone' => 'America/Lima',
            'brand_primary_color' => '#1F2937',
            'brand_secondary_color' => '#F59E0B',
            'ticket_footer' => '¡Gracias por su preferencia! Vuelva pronto.',
            'currency_symbol' => 'S/',

            // ── SUNAT (valores por defecto para BETA) ───────────────────
            'sunat_ruc' => '20000000001',
            'sunat_razon_social' => 'EMPRESA DEMO SAC',
            'sunat_nombre_comercial' => 'DEMO',
            'sunat_direccion_fiscal' => 'AV. PRINCIPAL 123',
            'sunat_ubigeo' => '150101',          // Lima/Lima/Lima
            'sunat_departamento' => 'LIMA',
            'sunat_provincia' => 'LIMA',
            'sunat_distrito' => 'LIMA',
            'sunat_urbanizacion' => '-',
            'sunat_codigo_pais' => 'PE',
            'sunat_igv_rate' => '18',            // %
            'sunat_environment' => 'beta',       // beta | produccion
            'sunat_cert_path' => '',             // Storage relative path
        ];

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        if ($isNewInstallation) {
            Setting::put('setup_status', 'pending');
            Setting::put('setup_version', '1.0');
        }
    }
}
