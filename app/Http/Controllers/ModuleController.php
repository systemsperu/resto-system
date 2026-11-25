<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateModuleRequest;
use App\Models\SystemAudit;
use App\Modules\Exceptions\ModuleDependencyException;
use App\Modules\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(ModuleManager $manager): View
    {
        $states = $manager->states();
        $modules = [];

        foreach ($states as $key => $state) {
            $dependents = array_keys(array_filter(
                $states,
                fn ($candidate): bool => in_array($key, $candidate->dependencies, true)
            ));

            $modules[$key] = [
                'key' => $state->key,
                'name' => $state->name,
                'description' => '',
                'enabled' => $state->enabled,
                'available' => true,
                'dependencies' => $state->dependencies,
                'dependents' => $dependents,
                'status_reason' => null,
            ];
        }

        return view('modules.index', compact('modules'));
    }

    public function update(
        UpdateModuleRequest $request,
        string $module,
        ModuleManager $manager
    ): RedirectResponse {
        $enabled = $request->boolean('enabled');
        $previous = $manager->isEnabled($module);

        try {
            DB::transaction(function () use ($request, $manager, $module, $enabled, $previous): void {
                $manager->setEnabled($module, $enabled);

                if ($previous !== $enabled) {
                    SystemAudit::create([
                        'user_id' => $request->user()->getKey(),
                        'action' => 'module.updated',
                        'target' => $module,
                        'status' => 'success',
                        'metadata' => ['previous' => $previous, 'enabled' => $enabled],
                    ]);
                }
            });
        } catch (ModuleDependencyException $exception) {
            return back()->withErrors(['module' => $exception->getMessage()]);
        }

        return back()->with('success', $enabled ? 'Módulo habilitado.' : 'Módulo deshabilitado.');
    }
}
