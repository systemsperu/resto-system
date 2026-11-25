<?php

namespace App\Branding;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class BrandSettings
{
    public const COLOR_KEYS = ['brand_primary_color', 'brand_secondary_color'];

    public const RESOURCE_KEYS = ['company_logo', 'brand_icon', 'favicon'];

    private const DEFAULT_PRIMARY_COLOR = '#1F2937';

    private const DEFAULT_SECONDARY_COLOR = '#F59E0B';

    public function get(): BrandContext
    {
        $keys = ['company_name', ...self::COLOR_KEYS, ...self::RESOURCE_KEYS];
        $values = Setting::query()->whereIn('key', $keys)->pluck('value', 'key');

        return new BrandContext(
            companyName: (string) ($values['company_name'] ?? config('app.name')),
            primaryColor: $this->safeColor(
                $values['brand_primary_color'] ?? null,
                self::DEFAULT_PRIMARY_COLOR,
                '#FFFFFF'
            ),
            secondaryColor: $this->safeColor(
                $values['brand_secondary_color'] ?? null,
                self::DEFAULT_SECONDARY_COLOR,
                '#000000'
            ),
            logoPath: $this->nullablePath($values['company_logo'] ?? null),
            iconPath: $this->nullablePath($values['brand_icon'] ?? null),
            faviconPath: $this->nullablePath($values['favicon'] ?? null),
        );
    }

    public function updateColors(array $values): BrandContext
    {
        foreach (array_intersect_key($values, array_flip(self::COLOR_KEYS)) as $key => $value) {
            Setting::put($key, strtoupper((string) $value));
        }

        return $this->get();
    }

    /**
     * Reemplaza o retira recursos sin borrar el anterior antes de persistir el nuevo.
     * Solo elimina archivos dentro de los directorios administrados por la aplicación.
     */
    public function updateResources(array $files, array $remove = []): BrandContext
    {
        foreach (self::RESOURCE_KEYS as $key) {
            $file = $files[$key] ?? null;

            if ($file instanceof UploadedFile) {
                $this->replace($key, $file);
            } elseif (($remove[$key] ?? false) === true) {
                $this->remove($key);
            }
        }

        return $this->get();
    }

    private function replace(string $key, UploadedFile $file): void
    {
        $oldPath = Setting::query()->where('key', $key)->value('value');
        $newPath = $file->store('branding', 'public');

        try {
            Setting::put($key, $newPath);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        if (is_string($oldPath) && $oldPath !== $newPath) {
            $this->deleteManagedFile($oldPath);
        }
    }

    private function remove(string $key): void
    {
        $oldPath = Setting::query()->where('key', $key)->value('value');
        Setting::put($key, null);

        if (is_string($oldPath)) {
            $this->deleteManagedFile($oldPath);
        }
    }

    private function deleteManagedFile(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        $isManaged = ! str_contains($normalized, '..')
            && (str_starts_with($normalized, 'branding/') || str_starts_with($normalized, 'settings/'));

        if ($isManaged) {
            Storage::disk('public')->delete($normalized);
        }
    }

    private function nullablePath(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $value);

        return ! str_contains($normalized, '..')
            && (str_starts_with($normalized, 'branding/') || str_starts_with($normalized, 'settings/'))
                ? $normalized
                : null;
    }

    private function safeColor(mixed $value, string $default, string $foreground): string
    {
        if (! is_string($value) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            return $default;
        }

        $normalized = strtoupper($value);

        return ColorContrast::passesAa($foreground, $normalized) ? $normalized : $default;
    }
}
