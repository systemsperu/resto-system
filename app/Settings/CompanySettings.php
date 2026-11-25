<?php

namespace App\Settings;

use App\Models\Setting;

final class CompanySettings
{
    public const KEYS = [
        'company_name', 'company_phone', 'company_email', 'company_website',
        'company_address', 'company_country_code', 'company_department',
        'company_province', 'company_district', 'company_ubigeo', 'timezone',
    ];

    public function get(): CompanyProfile
    {
        $values = Setting::query()->whereIn('key', self::KEYS)->pluck('value', 'key');

        return new CompanyProfile(
            name: (string) ($values['company_name'] ?? config('app.name')),
            phone: $this->nullableString($values['company_phone'] ?? null),
            email: $this->nullableString($values['company_email'] ?? null),
            website: $this->nullableString($values['company_website'] ?? null),
            address: $this->nullableString($values['company_address'] ?? null),
            countryCode: $this->nullableString($values['company_country_code'] ?? null),
            department: $this->nullableString($values['company_department'] ?? null),
            province: $this->nullableString($values['company_province'] ?? null),
            district: $this->nullableString($values['company_district'] ?? null),
            ubigeo: $this->nullableString($values['company_ubigeo'] ?? null),
            timezone: (string) ($values['timezone'] ?? config('app.timezone')),
        );
    }

    /** Persiste exclusivamente las claves conocidas del perfil de empresa. */
    public function update(array $attributes): CompanyProfile
    {
        foreach (array_intersect_key($attributes, array_flip(self::KEYS)) as $key => $value) {
            Setting::put($key, is_string($value) && $value === '' ? null : $value);
        }

        return $this->get();
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
