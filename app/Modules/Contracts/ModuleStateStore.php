<?php

namespace App\Modules\Contracts;

interface ModuleStateStore
{
    /**
     * @return list<string>|null Null indica que aún no existe configuración persistida.
     */
    public function enabledKeys(): ?array;

    /**
     * @param list<string> $keys
     */
    public function saveEnabledKeys(array $keys): void;
}
