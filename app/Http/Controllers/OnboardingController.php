<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOnboardingStepRequest;
use App\Onboarding\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

class OnboardingController extends Controller
{
    public function show(Request $request, OnboardingService $onboarding): View|RedirectResponse
    {
        $state = $onboarding->state();

        if ($state->isCompleted()) {
            return redirect()->route('dashboard');
        }

        $requestedStep = $request->integer('step');
        $allowedSteps = array_unique([...$state->completedSteps, $state->currentStep]);
        $currentStep = in_array($requestedStep, $allowedSteps, true)
            ? $requestedStep
            : $state->currentStep;

        return view('onboarding.show', [
            'onboarding' => $state->toArray(),
            'currentStep' => $currentStep,
        ]);
    }

    public function store(StoreOnboardingStepRequest $request, OnboardingService $onboarding): RedirectResponse
    {
        if ($onboarding->state()->isCompleted()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validated();
        $step = (int) $validated['step'];

        if ($step === 5) {
            try {
                $onboarding->complete($request->user());
            } catch (LogicException $exception) {
                return back()->withErrors(['step' => $exception->getMessage()])->withInput();
            }

            return redirect()->route('dashboard')->with('success', 'Configuración inicial completada.');
        }

        $state = $onboarding->saveStep($step, $validated, $request->allFiles());

        return redirect()->route('onboarding.show', ['step' => $state->currentStep])
            ->with('success', 'Paso guardado correctamente.');
    }

    public function exit(): RedirectResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            return redirect()->route('onboarding.show')
                ->with('warning', 'Completa la configuración inicial antes de acceder al sistema.');
        }

        return redirect()->route('dashboard')
            ->with('warning', 'La configuración inicial continúa pendiente.');
    }
}
