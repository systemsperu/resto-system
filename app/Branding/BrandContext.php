<?php

namespace App\Branding;

final readonly class BrandContext
{
    public function __construct(
        public string $companyName,
        public string $primaryColor,
        public string $secondaryColor,
        public ?string $logoPath,
        public ?string $iconPath,
        public ?string $faviconPath,
    ) {}
}
