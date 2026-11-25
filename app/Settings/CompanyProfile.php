<?php

namespace App\Settings;

final readonly class CompanyProfile
{
    public function __construct(
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $website,
        public ?string $address,
        public ?string $countryCode,
        public ?string $department,
        public ?string $province,
        public ?string $district,
        public ?string $ubigeo,
        public string $timezone,
    ) {}
}
