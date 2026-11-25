<?php

namespace App\Http\Middleware;

use App\Onboarding\OnboardingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! app(OnboardingService::class)->requiresSetup()) {
            return $next($request);
        }

        if ($request->routeIs('onboarding.*', 'logout')) {
            return $next($request);
        }

        if ($user->hasPermission('settings.manage')) {
            return redirect()->route('onboarding.show');
        }

        abort(503, 'La configuración inicial debe ser completada por un administrador.');
    }
}
