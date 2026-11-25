<?php

namespace Tests\Unit;

use App\Modules\Contracts\ModuleStateStore;
use App\Modules\Exceptions\ModuleDependencyException;
use App\Modules\ModuleManager;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class ModuleManagerTest extends TestCase
{
    private array $definitions = [
        'restaurant' => ['name' => 'Restaurante', 'default' => true, 'depends_on' => []],
        'inventory' => ['name' => 'Inventario', 'default' => true, 'depends_on' => ['restaurant']],
        'landing' => ['name' => 'Landing', 'default' => false, 'depends_on' => []],
        'marketing' => ['name' => 'Marketing', 'default' => false, 'depends_on' => ['landing']],
    ];

    public function test_uses_compatible_defaults_when_there_is_no_saved_state(): void
    {
        $manager = $this->manager();

        $this->assertTrue($manager->isEnabled('restaurant'));
        $this->assertTrue($manager->isEnabled('inventory'));
        $this->assertFalse($manager->isEnabled('landing'));
    }

    public function test_core_components_are_always_enabled_and_cannot_be_disabled(): void
    {
        $manager = $this->manager();

        $this->assertTrue($manager->isEnabled('identity'));

        $this->expectException(InvalidArgumentException::class);
        $manager->setEnabled('identity', false);
    }

    public function test_requires_dependencies_before_enabling_a_module(): void
    {
        $manager = $this->manager(['restaurant']);

        $this->expectException(ModuleDependencyException::class);
        $manager->setEnabled('marketing', true);
    }

    public function test_does_not_disable_a_module_required_by_an_enabled_module(): void
    {
        $manager = $this->manager(['restaurant', 'inventory']);

        $this->expectException(ModuleDependencyException::class);
        $manager->setEnabled('restaurant', false);
    }

    public function test_persists_only_state_and_does_not_touch_module_data(): void
    {
        $store = new InMemoryModuleStateStore(['restaurant', 'inventory']);
        $manager = $this->manager(store: $store);

        $manager->setEnabled('inventory', false);

        $this->assertSame(['restaurant'], $store->enabledKeys());
    }

    public function test_does_not_activate_an_orphaned_persisted_module(): void
    {
        $manager = $this->manager(['inventory']);

        $this->assertFalse($manager->isEnabled('inventory'));
        $this->assertSame([], $manager->enabledKeys());
    }

    public function test_rejects_unknown_modules(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager()->isEnabled('unknown');
    }

    public function test_rejects_cyclic_registry(): void
    {
        $this->expectException(LogicException::class);

        new ModuleManager(new InMemoryModuleStateStore(), [
            'one' => ['name' => 'Uno', 'default' => false, 'depends_on' => ['two']],
            'two' => ['name' => 'Dos', 'default' => false, 'depends_on' => ['one']],
        ], []);
    }

    private function manager(
        ?array $enabled = null,
        ?InMemoryModuleStateStore $store = null,
    ): ModuleManager {
        return new ModuleManager(
            $store ?? new InMemoryModuleStateStore($enabled),
            $this->definitions,
            ['identity' => 'Identidad', 'access' => 'Acceso'],
        );
    }
}

class InMemoryModuleStateStore implements ModuleStateStore
{
    /** @param list<string>|null $enabled */
    public function __construct(private ?array $enabled = null)
    {
    }

    public function enabledKeys(): ?array
    {
        return $this->enabled;
    }

    public function saveEnabledKeys(array $keys): void
    {
        $this->enabled = $keys;
    }
}
