<?php

namespace App\Modules;

final readonly class ModuleState
{
    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        public string $key,
        public string $name,
        public bool $enabled,
        public array $dependencies,
    ) {
    }
}
