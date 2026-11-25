<?php

namespace App\Http\Controllers;

use App\Onboarding\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return $this->redirectUser();
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return $this->redirectUser();
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->withHeaders(['Clear-Site-Data' => '"cache", "storage"']);
    }

    // Función auxiliar para redirigir inteligentemente
    protected function redirectUser()
    {
        $role = Auth::user()->role;

        if ($role === 'admin') {
            if (app(OnboardingService::class)->requiresSetup()) {
                return redirect()->route('onboarding.show');
            }

            return redirect()->route('dashboard'); // El jefe va al panel de control
        }

        if ($role === 'kitchen') {
            return redirect()->route('kitchen.index');
        }

        // Cajeros y Meseros van directo al trabajo (POS)
        return redirect()->route('pos.index');
    }
}
