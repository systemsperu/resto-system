<?php

namespace App\Modules;

use App\Modules\Contracts\ModuleStateStore;
use App\Modules\Exceptions\ModuleDependencyException;
use InvalidArgumentException;
use LogicException;

class ModuleManager
{
    /** @var array<string, array{name: string, default: bool, depends_on: list<string>}> */
    private array $definitions;

    /** @var array<string, string> */
    private array $core;

    /**
     * @param array<string, array{name: string, default: bool, depends_on: list<string>}>|null $definitions
     * @param array<string, string>|null $core
     */
    public function __construct(
        private readonly ModuleStateStore $store,
        ?array $definitions = null,
        ?array $core = null,
    ) {
        $this->definitions = $definitions ?? config('modules.available', []);
        $this->core = $core ?? config('modules.core', []);
        $this->validateRegistry();
    }

    /**
     * @return array<string, string>
     */
    public function core(): array
    {
        return $this->core;
    }

    public function isCore(string $key): bool
    {
        return array_key_exists($key, $this->core);
    }

    public function exists(string $key): bool
    {
        return $this->isCore($key) || array_key_exists($key, $this->definitions);
    }

    public function isEnabled(string $key): bool
    {
        if ($this->isCore($key)) {
            return true;
        }

        $this->definition($key);

        return in_array($key, $this->enabledKeys(), true);
    }

    /**
     * @return list<string>
     */
    public function enabledKeys(): array
    {
        $stored = $this->store->enabledKeys();

        if ($stored === null) {
            $stored = array_keys(array_filter(
                $this->definitions,
                static fn (array $definition): bool => $definition['default'],
            ));
        }

        $enabled = array_values(array_intersect(array_keys($this->definitions), array_unique($stored)));
        $changed = true;

        // Un estado manipulado o antiguo nunca debe saltarse sus dependencias.
        while ($changed) {
            $changed = false;

            foreach ($enabled as $key) {
                if (array_diff($this->dependenciesOf($key), $enabled) !== []) {
                    $enabled = array_values(array_diff($enabled, [$key]));
                    $changed = true;
                }
            }
        }

        return $enabled;
    }

    /**
     * @return array<string, ModuleState>
     */
    public function states(): array
    {
        $enabled = $this->enabledKeys();
        $states = [];

        foreach ($this->definitions as $key => $definition) {
            $states[$key] = new ModuleState(
                $key,
                $definition['name'],
                in_array($key, $enabled, true),
                $definition['depends_on'],
            );
        }

        return $states;
    }

    public function setEnabled(string $key, bool $enabled): void
    {
        if ($this->isCore($key)) {
            if (! $enabled) {
                throw new InvalidArgumentException("El componente de núcleo '{$key}' no se puede deshabilitar.");
            }

            return;
        }

        $this->definition($key);
        $enabledKeys = $this->enabledKeys();

        if ($enabled) {
            $missing = array_values(array_diff($this->dependenciesOf($key), $enabledKeys));

            if ($missing !== []) {
                throw ModuleDependencyException::missing($key, $missing);
            }

            if (! in_array($key, $enabledKeys, true)) {
                $enabledKeys[] = $key;
            }
        } else {
            $dependents = $this->enabledDependentsOf($key, $enabledKeys);

            if ($dependents !== []) {
                throw ModuleDependencyException::requiredBy($key, $dependents);
            }

            $enabledKeys = array_values(array_diff($enabledKeys, [$key]));
        }

        $this->store->saveEnabledKeys($this->ordered($enabledKeys));
    }

    /**
     * @return list<string>
     */
    public function dependenciesOf(string $key): array
    {
        return $this->definition($key)['depends_on'];
    }

    /**
     * @return list<string>
     */
    private function enabledDependentsOf(string $key, array $enabledKeys): array
    {
        $dependents = [];

        foreach ($this->definitions as $candidate => $definition) {
            if (in_array($candidate, $enabledKeys, true)
                && in_array($key, $definition['depends_on'], true)) {
                $dependents[] = $candidate;
            }
        }

        return $dependents;
    }

    /**
     * @return array{name: string, default: bool, depends_on: list<string>}
     */
    private function definition(string $key): array
    {
        if (! array_key_exists($key, $this->definitions)) {
            throw new InvalidArgumentException("Módulo desconocido: {$key}.");
        }

        return $this->definitions[$key];
    }

    private function validateRegistry(): void
    {
        foreach ($this->definitions as $key => $definition) {
            foreach ($definition['depends_on'] as $dependency) {
                if (! array_key_exists($dependency, $this->definitions)) {
                    throw new LogicException("El módulo '{$key}' depende del módulo inexistente '{$dependency}'.");
                }

                if ($dependency === $key) {
                    throw new LogicException("El módulo '{$key}' no puede depender de sí mismo.");
                }

                if ($definition['default'] && ! $this->definitions[$dependency]['default']) {
                    throw new LogicException(
                        "El módulo '{$key}' está activo por defecto, pero su dependencia '{$dependency}' no."
                    );
                }
            }
        }

        foreach (array_keys($this->definitions) as $key) {
            $this->assertNoCycle($key, []);
        }
    }

    /**
     * @param list<string> $path
     */
    private function assertNoCycle(string $key, array $path): void
    {
        if (in_array($key, $path, true)) {
            throw new LogicException('Se detectó un ciclo entre módulos: '.implode(' -> ', [...$path, $key]));
        }

        foreach ($this->definitions[$key]['depends_on'] as $dependency) {
            $this->assertNoCycle($dependency, [...$path, $key]);
        }
    }

    /**
     * @param list<string> $keys
     * @return list<string>
     */
    private function ordered(array $keys): array
    {
        return array_values(array_intersect(array_keys($this->definitions), array_unique($keys)));
    }
}
