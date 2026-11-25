<?php

namespace App\Onboarding;

use App\Branding\BrandSettings;
use App\Models\Setting;
use App\Models\SystemAudit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use LogicException;

final class OnboardingService
{
    public const VERSION = '1.0';

    private const REQUIRED_STEPS = [1, 2, 3, 4];

    public function __construct(private readonly BrandSettings $brandSettings) {}

    public function state(): OnboardingState
    {
        $settings = Setting::query()->pluck('value', 'key');
        $explicitStatus = $settings['setup_status'] ?? null;
        $legacy = $explicitStatus === null && filled($settings['company_name'] ?? null);
        $status = $legacy ? 'completed' : (string) ($explicitStatus ?? 'pending');
        $completed = $this->completedSteps($settings['setup_completed_steps'] ?? null);
        $current = $status === 'completed' ? 5 : $this->firstIncompleteStep($completed);

        return new OnboardingState(
            status: $status,
            currentStep: $current,
            completedSteps: $completed,
            version: (string) ($settings['setup_version'] ?? self::VERSION),
            legacyInstallation: $legacy,
            values: $this->values($settings->all()),
        );
    }

    public function requiresSetup(): bool
    {
        return Setting::query()
            ->where('key', 'setup_status')
            ->whereIn('value', ['pending', 'in_progress'])
            ->exists();
    }

    public function saveStep(int $step, array $data, array $files = []): OnboardingState
    {
        if (! in_array($step, self::REQUIRED_STEPS, true)) {
            throw new LogicException('Solo se pueden guardar los pasos 1 al 4.');
        }

        if ($step === 2) {
            $this->saveBrand($data, $files);
        } else {
            DB::transaction(fn () => $this->saveValues($step, $data));
        }

        $this->markStepCompleted($step);

        return $this->state();
    }

    public function complete(User $user): OnboardingState
    {
        $state = $this->state();
        $missing = array_values(array_diff(self::REQUIRED_STEPS, $state->completedSteps));

        if ($missing !== []) {
            throw new LogicException('Faltan pasos obligatorios antes de completar el asistente.');
        }

        DB::transaction(function () use ($user): void {
            Setting::put('setup_status', 'completed');
            Setting::put('setup_current_step', '5');
            Setting::put('setup_version', self::VERSION);
            Setting::put('setup_completed_at', now()->toIso8601String());
            Setting::put('setup_completed_by', (string) $user->getKey());

            SystemAudit::create([
                'user_id' => $user->getKey(),
                'action' => 'onboarding.completed',
                'target' => 'installation',
                'status' => 'success',
                'metadata' => ['version' => self::VERSION, 'steps' => self::REQUIRED_STEPS],
            ]);
        });

        return $this->state();
    }

    private function saveValues(int $step, array $data): void
    {
        $maps = [
            1 => [
                'business_name' => 'company_name', 'legal_name' => 'legal_name',
                'tax_id' => 'tax_id', 'business_tagline' => 'business_tagline',
                'business_type' => 'business_type',
            ],
            3 => [
                'contact_phone' => 'company_phone', 'contact_whatsapp' => 'contact_whatsapp',
                'contact_email' => 'company_email', 'address_line' => 'company_address',
                'address_department' => 'company_department',
                'address_province' => 'company_province', 'address_district' => 'company_district',
                'address_ubigeo' => 'company_ubigeo', 'address_reference' => 'address_reference',
                'latitude' => 'latitude', 'longitude' => 'longitude',
            ],
            4 => [
                'country_code' => 'company_country_code', 'locale' => 'locale',
                'timezone' => 'timezone', 'currency_code' => 'currency_code',
                'date_format' => 'date_format', 'week_starts_on' => 'week_starts_on',
                'ticket_footer' => 'ticket_footer',
            ],
        ];

        foreach ($maps[$step] as $input => $key) {
            if (array_key_exists($input, $data)) {
                Setting::put($key, $data[$input] === '' ? null : $data[$input]);
            }
        }

        if ($step === 4) {
            Setting::put('currency_symbol', $data['currency_code'] === 'PEN' ? 'S/' : '$');
        }
    }

    private function saveBrand(array $data, array $files): void
    {
        $this->brandSettings->updateColors($data);
        $this->brandSettings->updateResources(
            files: array_filter([
                'company_logo' => $files['company_logo'] ?? null,
                'brand_icon' => $files['brand_icon'] ?? null,
                'favicon' => $files['favicon'] ?? null,
            ], fn (mixed $file): bool => $file instanceof UploadedFile),
            remove: [
                'company_logo' => (bool) ($data['remove_company_logo'] ?? false),
                'brand_icon' => (bool) ($data['remove_brand_icon'] ?? false),
                'favicon' => (bool) ($data['remove_favicon'] ?? false),
            ],
        );
    }

    private function markStepCompleted(int $step): void
    {
        DB::transaction(function () use ($step): void {
            $state = $this->state();
            $completed = array_values(array_unique([...$state->completedSteps, $step]));
            sort($completed);

            Setting::put('setup_status', 'in_progress');
            Setting::put('setup_completed_steps', json_encode($completed, JSON_THROW_ON_ERROR));
            Setting::put('setup_current_step', (string) $this->firstIncompleteStep($completed));
            Setting::put('setup_version', self::VERSION);

            if (! Setting::query()->where('key', 'setup_started_at')->whereNotNull('value')->exists()) {
                Setting::put('setup_started_at', now()->toIso8601String());
            }
        });
    }

    private function completedSteps(mixed $value): array
    {
        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? array_values(array_intersect(self::REQUIRED_STEPS, array_map('intval', $decoded)))
            : [];
    }

    private function firstIncompleteStep(array $completed): int
    {
        foreach (self::REQUIRED_STEPS as $step) {
            if (! in_array($step, $completed, true)) {
                return $step;
            }
        }

        return 5;
    }

    private function values(array $settings): array
    {
        return [
            'business_name' => $settings['company_name'] ?? null,
            'legal_name' => $settings['legal_name'] ?? null,
            'tax_id' => $settings['tax_id'] ?? null,
            'business_tagline' => $settings['business_tagline'] ?? null,
            'business_type' => $settings['business_type'] ?? null,
            'company_logo' => $settings['company_logo'] ?? null,
            'brand_icon' => $settings['brand_icon'] ?? null,
            'favicon' => $settings['favicon'] ?? null,
            'brand_primary_color' => $settings['brand_primary_color'] ?? '#1F2937',
            'brand_secondary_color' => $settings['brand_secondary_color'] ?? '#F59E0B',
            'contact_phone' => $settings['company_phone'] ?? null,
            'contact_whatsapp' => $settings['contact_whatsapp'] ?? null,
            'contact_email' => $settings['company_email'] ?? null,
            'address_line' => $settings['company_address'] ?? null,
            'address_department' => $settings['company_department'] ?? null,
            'address_province' => $settings['company_province'] ?? null,
            'address_district' => $settings['company_district'] ?? null,
            'address_ubigeo' => $settings['company_ubigeo'] ?? null,
            'address_reference' => $settings['address_reference'] ?? null,
            'latitude' => $settings['latitude'] ?? null,
            'longitude' => $settings['longitude'] ?? null,
            'country_code' => $settings['company_country_code'] ?? 'PE',
            'locale' => $settings['locale'] ?? 'es-PE',
            'timezone' => $settings['timezone'] ?? 'America/Lima',
            'currency_code' => $settings['currency_code'] ?? 'PEN',
            'date_format' => $settings['date_format'] ?? 'dd/MM/yyyy',
            'week_starts_on' => (int) ($settings['week_starts_on'] ?? 1),
            'ticket_footer' => $settings['ticket_footer'] ?? null,
        ];
    }
}
