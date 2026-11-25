<?php

namespace App\Modules;

use App\Models\Setting;
use App\Modules\Contracts\ModuleStateStore;

class SettingModuleStateStore implements ModuleStateStore
{
    private const SETTING_KEY = 'enabled_modules';

    public function enabledKeys(): ?array
    {
        $value = Setting::query()->where('key', self::SETTING_KEY)->value('value');

        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return null;
        }

        return array_values(array_filter($decoded, 'is_string'));
    }

    public function saveEnabledKeys(array $keys): void
    {
        Setting::put(self::SETTING_KEY, json_encode(array_values($keys), JSON_THROW_ON_ERROR));
    }
}
