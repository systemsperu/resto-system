<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    public const SECRET_KEYS = [
        'sunat_sol_user',
        'sunat_sol_pass',
        'sunat_cert_password',
    ];

    protected $fillable = ['key', 'value', 'is_secret'];

    protected $casts = [
        'is_secret' => 'boolean',
    ];

    public static function put(string $key, mixed $value): self
    {
        $isSecret = in_array($key, self::SECRET_KEYS, true);

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $isSecret && filled($value) ? Crypt::encryptString((string) $value) : $value,
                'is_secret' => $isSecret,
            ]
        );
    }

    public static function decryptedValues(): array
    {
        return self::query()->get()->mapWithKeys(function (self $setting): array {
            $value = $setting->value;

            if ($setting->is_secret && filled($value)) {
                $value = Crypt::decryptString($value);
            }

            return [$setting->key => $value];
        })->all();
    }

    public static function publicValues(): array
    {
        return self::query()->where('is_secret', false)->pluck('value', 'key')->all();
    }

    public static function hasSecret(string $key): bool
    {
        return self::query()
            ->where('key', $key)
            ->where('is_secret', true)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->exists();
    }
}
