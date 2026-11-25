<?php

namespace App\Http\Middleware;

use App\Modules\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleManager $modules)
    {
    }

    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! $this->modules->exists($module) || ! $this->modules->isEnabled($module)) {
            throw new NotFoundHttpException();
        }

        return $next($request);
    }
}
