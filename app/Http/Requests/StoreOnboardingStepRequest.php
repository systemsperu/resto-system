<?php

namespace App\Http\Requests;

use App\Branding\ColorContrast;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOnboardingStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->hasPermission('settings.manage');
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach ($this->except(['company_logo', 'brand_icon', 'favicon']) as $key => $value) {
            if (is_string($value)) {
                $normalized[$key] = trim($value);
            }
        }
        foreach (['country_code', 'currency_code', 'brand_primary_color', 'brand_secondary_color'] as $key) {
            if (isset($normalized[$key])) {
                $normalized[$key] = strtoupper($normalized[$key]);
            }
        }
        $this->merge($normalized);
    }

    public function rules(): array
    {
        $step = (int) $this->input('step');
        $common = ['step' => ['required', 'integer', 'between:1,5']];

        return $common + match ($step) {
            1 => [
                'business_name' => ['required', 'string', 'min:2', 'max:120'],
                'legal_name' => ['nullable', 'string', 'min:2', 'max:200'],
                'tax_id' => ['nullable', 'digits:11'],
                'business_tagline' => ['nullable', 'string', 'max:120'],
                'business_type' => ['required', Rule::in([
                    'restaurant', 'tourist_recreation', 'restaurant_lodging', 'lodging', 'other',
                ])],
            ],
            2 => [
                'brand_primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'brand_secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'brand_icon' => ['nullable', 'image', 'mimes:png,webp', 'max:1024'],
                'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
                'remove_company_logo' => ['sometimes', 'boolean'],
                'remove_brand_icon' => ['sometimes', 'boolean'],
                'remove_favicon' => ['sometimes', 'boolean'],
            ],
            3 => [
                'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]+$/'],
                'contact_whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{6,15}$/'],
                'contact_email' => ['nullable', 'email:rfc', 'max:255'],
                'address_line' => ['required', 'string', 'max:250'],
                'address_department' => ['required', 'string', 'max:100'],
                'address_province' => ['required', 'string', 'max:100'],
                'address_district' => ['required', 'string', 'max:100'],
                'address_ubigeo' => ['nullable', 'digits:6'],
                'address_reference' => ['nullable', 'string', 'max:180'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ],
            4 => [
                'country_code' => ['required', 'string', 'size:2', 'alpha:ascii'],
                'locale' => ['required', Rule::in(['es-PE', 'es', 'en-US'])],
                'timezone' => ['required', 'timezone:all'],
                'currency_code' => ['required', Rule::in(['PEN', 'USD'])],
                'date_format' => ['required', Rule::in(['dd/MM/yyyy', 'yyyy-MM-dd'])],
                'week_starts_on' => ['required', 'integer', 'between:0,6'],
                'ticket_footer' => ['nullable', 'string', 'max:280'],
            ],
            5 => ['confirmed' => ['accepted']],
            default => [],
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((int) $this->input('step') !== 2) {
                return;
            }

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
            if (filled($secondary)
                && is_string($secondary)
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
