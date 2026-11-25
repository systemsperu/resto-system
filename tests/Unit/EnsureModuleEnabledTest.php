<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureModuleEnabled;
use App\Modules\ModuleManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureModuleEnabledTest extends \PHPUnit\Framework\TestCase
{
    public function test_allows_an_enabled_module(): void
    {
        $manager = new ModuleManager(
            new InMemoryModuleStateStore(['restaurant']),
            ['restaurant' => ['name' => 'Restaurante', 'default' => true, 'depends_on' => []]],
            [],
        );
        $middleware = new EnsureModuleEnabled($manager);

        $response = $middleware->handle(
            Request::create('/pos'),
            static fn () => new \Symfony\Component\HttpFoundation\Response('ok'),
            'restaurant',
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_hides_a_disabled_module(): void
    {
        $manager = new ModuleManager(
            new InMemoryModuleStateStore([]),
            ['landing' => ['name' => 'Landing', 'default' => false, 'depends_on' => []]],
            [],
        );
        $middleware = new EnsureModuleEnabled($manager);

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(Request::create('/landing'), static fn () => null, 'landing');
    }

    public function test_hides_an_unknown_module(): void
    {
        $manager = new ModuleManager(new InMemoryModuleStateStore(), [], []);
        $middleware = new EnsureModuleEnabled($manager);

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(Request::create('/unknown'), static fn () => null, 'unknown');
    }
}
