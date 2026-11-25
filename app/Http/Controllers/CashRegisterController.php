<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\SystemAudit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegisterController extends Controller
{
    public function index()
    {
        $registers = CashRegister::with('user')->orderBy('created_at', 'desc')->paginate(15);

        return view('cash_registers.index', compact('registers'));
    }

    public function create()
    {
        if (auth()->user()->activeCashRegister) {
            return redirect()->route('pos.index')->with('info', 'Ya tienes un turno de caja abierto.');
        }

        return view('cash_registers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'opening_amount' => 'required|numeric|min:0',
        ]);

        if (auth()->user()->activeCashRegister) {
            return redirect()->route('pos.index')->with('error', 'Ya tienes una caja abierta.');
        }

        DB::transaction(function () use ($request): void {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($request->user()->cashRegisters()->where('status', 'open')->exists()) {
                throw ValidationException::withMessages(['cash_register' => 'Ya tienes una caja abierta.']);
            }

            $register = CashRegister::create([
                'user_id' => $request->user()->id,
                'opening_time' => Carbon::now(),
                'opening_amount' => $request->opening_amount,
                'status' => 'open',
            ]);
            $this->audit($request, 'cash_register.opened', $register);
        });

        return redirect()->route('pos.index')->with('success', 'Turno de caja abierto correctamente.');
    }

    public function close()
    {
        $cashRegister = auth()->user()->activeCashRegister;

        if (! $cashRegister) {
            return redirect()->route('cash_registers.create')->with('error', 'No tienes ninguna caja abierta para cerrar.');
        }

        // Calcular el esperado:
        // Monto inicial + Total ventas efectivo - Total gastos
        $totalSalesCash = $cashRegister->payments()->where('method', 'cash')->where('status', 'confirmed')->sum('amount');
        $totalExpenses = $cashRegister->expenses()->sum('amount');

        $expectedAmount = $cashRegister->opening_amount + $totalSalesCash - $totalExpenses;

        return view('cash_registers.close', compact('cashRegister', 'expectedAmount'));
    }

    public function processClose(Request $request)
    {
        $request->validate([
            'closing_amount' => 'required|numeric|min:0',
        ]);

        $difference = DB::transaction(function () use ($request): float {
            $cashRegister = CashRegister::where('user_id', $request->user()->id)->where('status', 'open')->lockForUpdate()->first();
            if (! $cashRegister) {
                throw ValidationException::withMessages(['cash_register' => 'No tienes una caja abierta para cerrar.']);
            }

            $totalSalesCash = $cashRegister->payments()->where('method', 'cash')->where('status', 'confirmed')->sum('amount');
            $totalExpenses = $cashRegister->expenses()->sum('amount');
            $expectedAmount = round((float) $cashRegister->opening_amount + (float) $totalSalesCash - (float) $totalExpenses, 2);
            $difference = round((float) $request->closing_amount - $expectedAmount, 2);

            $cashRegister->update([
                'closing_time' => Carbon::now(), 'closing_amount' => $request->closing_amount,
                'expected_amount' => $expectedAmount, 'difference' => $difference,
                'status' => 'closed', 'notes' => $request->notes,
            ]);
            $this->audit($request, 'cash_register.closed', $cashRegister, ['difference' => $difference]);

            return $difference;
        });

        $destination = auth()->user()->role === 'admin' ? 'dashboard' : 'sales.index';

        return redirect()->route($destination)->with('success', 'Turno de caja cerrado correctamente. Diferencia: S/ '.number_format($difference, 2));
    }

    private function audit(Request $request, string $action, CashRegister $register, array $metadata = []): void
    {
        SystemAudit::create([
            'user_id' => $request->user()->id, 'action' => $action,
            'target' => 'cash_register:'.$register->id, 'status' => 'success', 'metadata' => $metadata,
        ]);
    }
}
