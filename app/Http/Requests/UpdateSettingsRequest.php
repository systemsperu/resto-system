<?php

namespace App\Http\Requests;

use App\Branding\ColorContrast;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->hasPermission('settings.manage');
    }

    protected function prepareForValidation(): void
    {
        $fields = [
            'company_name', 'company_phone', 'company_email', 'company_website',
            'company_address', 'company_country_code', 'company_department',
            'company_province', 'company_district', 'company_ubigeo',
            'brand_primary_color', 'brand_secondary_color',
        ];
        $normalized = [];

        foreach ($fields as $field) {
            if ($this->exists($field) && is_string($this->input($field))) {
                $normalized[$field] = trim($this->input($field));
            }
        }
        if (isset($normalized['company_country_code'])) {
            $normalized['company_country_code'] = strtoupper($normalized['company_country_code']);
        }
        foreach (['brand_primary_color', 'brand_secondary_color'] as $color) {
            if (isset($normalized[$color])) {
                $normalized[$color] = strtoupper($normalized[$color]);
            }
        }
        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email:rfc', 'max:255'],
            'company_website' => ['nullable', 'url:http,https', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_country_code' => ['nullable', 'string', 'size:2', 'alpha:ascii'],
            'company_department' => ['nullable', 'string', 'max:100'],
            'company_province' => ['nullable', 'string', 'max:100'],
            'company_district' => ['nullable', 'string', 'max:100'],
            'company_ubigeo' => ['nullable', 'digits:6'],
            'brand_primary_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_secondary_color' => ['sometimes', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'brand_icon' => ['nullable', 'image', 'mimes:png,webp', 'max:1024'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'remove_company_logo' => ['sometimes', 'boolean'],
            'remove_brand_icon' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'timezone' => ['required', 'timezone:all'],
            'currency_symbol' => ['required', Rule::in(['S/', '$', '€'])],
            'ticket_footer' => ['nullable', 'string', 'max:500'],
            'sunat_ruc' => ['nullable', 'digits:11'],
            'sunat_razon_social' => ['nullable', 'string', 'max:255'],
            'sunat_nombre_comercial' => ['nullable', 'string', 'max:255'],
            'sunat_direccion_fiscal' => ['nullable', 'string', 'max:500'],
            'sunat_ubigeo' => ['nullable', 'digits:6'],
            'sunat_departamento' => ['nullable', 'string', 'max:100'],
            'sunat_provincia' => ['nullable', 'string', 'max:100'],
            'sunat_distrito' => ['nullable', 'string', 'max:100'],
            'sunat_urbanizacion' => ['nullable', 'string', 'max:100'],
            'sunat_environment' => ['required', Rule::in(config('sunat.send_enabled') ? ['beta', 'produccion'] : ['beta'])],
            'sunat_igv_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'sunat_sol_user' => ['nullable', 'string', 'max:100'],
            'sunat_sol_pass' => ['nullable', 'string', 'max:255'],
            'sunat_cert_password' => ['nullable', 'string', 'max:255'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sunat_cert_file' => ['nullable', 'file', 'max:512', 'extensions:pfx,p12'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $primary = $this->input('brand_primary_color');
            if (is_string($primary)
                && preg_match('/^#[0-9A-Fa-f]{6}$/', $primary)
                && ! ColorContrast::passesAa('#FFFFFF', $primary)) {
                $validator->errors()->add(
                    'brand_primary_color',
                    'El color primario debe alcanzar contraste WCAG AA (4.5:1) con texto blanco.'
                );
            }

            $secondary = $this->input('brand_secondary_color');
            if (is_string($secondary)
                && preg_match('/^#[0-9A-Fa-f]{6}$/', $secondary)
                && ! ColorContrast::passesAa('#000000', $secondary)) {
                $validator->errors()->add(
                    'brand_secondary_color',
                    'El color secundario debe alcanzar contraste WCAG AA (4.5:1) con texto negro.'
                );
            }
        });
    }
}
