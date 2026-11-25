<?php

namespace App\Modules\Exceptions;

use DomainException;

class ModuleDependencyException extends DomainException
{
    /**
     * @param list<string> $modules
     */
    public static function missing(string $module, array $modules): self
    {
        return new self(sprintf(
            'No se puede habilitar el módulo "%s". Primero habilita: %s.',
            $module,
            implode(', ', $modules),
        ));
    }

    /**
     * @param list<string> $modules
     */
    public static function requiredBy(string $module, array $modules): self
    {
        return new self(sprintf(
            'No se puede deshabilitar el módulo "%s". Está requerido por: %s.',
            $module,
            implode(', ', $modules),
        ));
    }
}
