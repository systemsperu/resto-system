<?php

namespace App\Http\Controllers;

use App\Branding\BrandSettings;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Settings\CompanySettings;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::publicValues();
        $secretStatus = collect(Setting::SECRET_KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => Setting::hasSecret($key)])
            ->all();
        $sunatSendEnabled = (bool) config('sunat.send_enabled');

        // Lista de zonas horarias comunes en Latinoamérica para el select
        $timezones = [
            'America/Lima' => '(UTC-05:00) Lima, Bogotá, Quito',
            'America/Caracas' => '(UTC-04:00) Caracas',
            'America/La_Paz' => '(UTC-04:00) La Paz',
            'America/Santiago' => '(UTC-03:00) Santiago',
            'America/Argentina/Buenos_Aires' => '(UTC-03:00) Buenos Aires',
            'America/Montevideo' => '(UTC-03:00) Montevideo',
            'America/Mexico_City' => '(UTC-06:00) Ciudad de México',
            'America/Tijuana' => '(UTC-08:00) Tijuana',
            'America/New_York' => '(UTC-05:00) Hora del Este (EE.UU.)',
            'Europe/Madrid' => '(UTC+01:00) Madrid',
            'UTC' => '(UTC+00:00) Tiempo Universal Coordinado',
        ];

        return view('settings.index', compact('settings', 'timezones', 'secretStatus', 'sunatSendEnabled'));
    }

    public function update(
        UpdateSettingsRequest $request,
        CompanySettings $companySettings,
        BrandSettings $brandSettings
    ) {
        $validated = $request->validated();
        $companySettings->update(array_intersect_key($validated, array_flip(CompanySettings::KEYS)));
        $brandSettings->updateColors(array_intersect_key($validated, array_flip(BrandSettings::COLOR_KEYS)));
        $brandSettings->updateResources(
            files: array_intersect_key($request->allFiles(), array_flip(BrandSettings::RESOURCE_KEYS)),
            remove: [
                'company_logo' => $request->boolean('remove_company_logo'),
                'brand_icon' => $request->boolean('remove_brand_icon'),
                'favicon' => $request->boolean('remove_favicon'),
            ],
        );

        $secretData = array_intersect_key($validated, array_flip(Setting::SECRET_KEYS));
        $publicData = array_diff_key(
            $validated,
            array_flip([
                ...Setting::SECRET_KEYS,
                ...CompanySettings::KEYS,
                ...BrandSettings::COLOR_KEYS,
                ...BrandSettings::RESOURCE_KEYS,
                'remove_company_logo',
                'remove_brand_icon',
                'remove_favicon',
                'sunat_cert_file',
            ])
        );

        foreach ($publicData as $key => $value) {
            if (! is_null($value)) {
                Setting::put($key, $value);
            }
        }

        // Un campo secreto vacío conserva la credencial existente.
        foreach ($secretData as $key => $value) {
            if (filled($value)) {
                Setting::put($key, $value);
            }
        }

        // 3. Guardar certificado SUNAT (.pfx)
        if ($request->hasFile('sunat_cert_file')) {
            $ext = strtolower($request->file('sunat_cert_file')->getClientOriginalExtension());
            if (! in_array($ext, ['pfx', 'p12'])) {
                return redirect()->back()->with('error', 'El certificado debe ser un archivo .pfx o .p12');
            }

            // Borrar cert anterior si existía y NO es el demo
            $oldCert = Setting::where('key', 'sunat_cert_path')->value('value');
            if ($oldCert
                && ! str_contains($oldCert, 'demo')
                && Storage::disk('local')->exists($oldCert)) {
                Storage::disk('local')->delete($oldCert);
            }

            // Guardar el nuevo en storage/app/sunat/certs/
            $filename = 'cert_'.date('Ymd_His').'.'.$ext;
            $request->file('sunat_cert_file')->storeAs('sunat/certs', $filename, 'local');

            Setting::put('sunat_cert_path', 'sunat/certs/'.$filename);
        }

        return redirect()->back()->with('success', 'Configuración actualizada correctamente.');
    }
}
