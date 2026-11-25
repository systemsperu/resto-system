<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\StoreWorkShiftRequest;
use App\Models\Employee;
use App\Models\SystemAudit;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = Employee::with(['user', 'shifts' => fn ($query) => $query->orderBy('starts_at')])
            ->orderByDesc('is_active')->orderBy('full_name')->get();
        $availableUsers = User::whereDoesntHave('employee')->orderBy('name')->get();

        return view('employees.index', compact('employees', 'availableUsers'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = DB::transaction(function () use ($request): Employee {
            $employee = Employee::create($request->validated());
            $this->audit($request->user()->id, 'employee.created', $employee);

            return $employee;
        });

        return back()->with('success', "Personal {$employee->full_name} registrado.");
    }

    public function update(StoreEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($request, $employee): void {
            $employee->update($request->validated());
            $this->audit($request->user()->id, 'employee.updated', $employee);
        });

        return back()->with('success', 'Ficha de personal actualizada.');
    }

    public function toggle(Employee $employee): RedirectResponse
    {
        $employee->update(['is_active' => ! $employee->is_active]);
        $this->audit(request()->user()->id, 'employee.status_changed', $employee);

        return back()->with('success', 'Estado del personal actualizado.');
    }

    public function storeShift(StoreWorkShiftRequest $request, Employee $employee): RedirectResponse
    {
        if (! $employee->is_active) {
            return back()->withErrors(['employee' => 'No se pueden programar turnos para personal inactivo.']);
        }

        $overlap = $employee->shifts()
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '<', $request->date('ends_at'))
            ->where('ends_at', '>', $request->date('starts_at'))
            ->exists();

        if ($overlap) {
            return back()->withErrors(['starts_at' => 'El turno se superpone con otro turno activo.']);
        }

        $shift = $employee->shifts()->create($request->validated());
        $this->audit($request->user()->id, 'work_shift.created', $employee, ['shift_id' => $shift->id]);

        return back()->with('success', 'Turno programado.');
    }

    public function cancelShift(WorkShift $shift): RedirectResponse
    {
        $shift->update(['status' => 'cancelled']);
        $this->audit(request()->user()->id, 'work_shift.cancelled', $shift->employee, ['shift_id' => $shift->id]);

        return back()->with('success', 'Turno cancelado.');
    }

    private function audit(int $userId, string $action, Employee $employee, array $metadata = []): void
    {
        SystemAudit::create([
            'user_id' => $userId,
            'action' => $action,
            'target' => 'employee:'.$employee->id,
            'status' => 'success',
            'metadata' => $metadata,
        ]);
    }
}
