<?php

namespace App\Onboarding;

final readonly class OnboardingState
{
    public function __construct(
        public string $status,
        public int $currentStep,
        public array $completedSteps,
        public string $version,
        public bool $legacyInstallation,
        public array $values,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'current_step' => $this->currentStep,
            'completed_steps' => $this->completedSteps,
            'version' => $this->version,
            'legacy_installation' => $this->legacyInstallation,
            'values' => $this->values,
        ];
    }
}
