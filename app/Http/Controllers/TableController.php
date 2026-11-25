<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTableWaiterRequest;
use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\StoreTableRequest;
use App\Models\Area;
use App\Models\Employee;
use App\Models\SystemAudit;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TableController extends Controller
{
    public function index(): View
    {
        $areas = Area::with(['tables' => fn ($query) => $query->with('assignedWaiter')->orderBy('name')])->orderBy('name')->get();
        $waiters = Employee::query()->with('user')->where('position', 'waiter')->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('role', 'waiter'))->orderBy('full_name')->get();

        return view('tables.index', compact('areas', 'waiters'));
    }

    public function storeArea(StoreAreaRequest $request): RedirectResponse
    {
        $area = Area::create($request->validated());
        $this->audit($request, 'area.created', 'area:'.$area->id);

        return back()->with('success', 'Área creada.');
    }

    public function updateArea(StoreAreaRequest $request, Area $area): RedirectResponse
    {
        $area->update($request->validated());
        $this->audit($request, 'area.updated', 'area:'.$area->id);

        return back()->with('success', 'Área actualizada.');
    }

    public function destroyArea(Request $request, Area $area): RedirectResponse
    {
        if ($area->tables()->exists()) {
            return back()->withErrors(['area' => 'Traslada o retira las mesas antes de eliminar el área.']);
        }

        $area->delete();
        $this->audit($request, 'area.deleted', 'area:'.$area->id);

        return back()->with('success', 'Área eliminada.');
    }

    public function storeTable(StoreTableRequest $request): RedirectResponse
    {
        $table = Table::create([...$request->validated(), 'status' => 'available', 'x_pos' => 50, 'y_pos' => 50]);
        $this->audit($request, 'table.created', 'table:'.$table->id);

        return back()->with('success', 'Mesa creada.');
    }

    public function updateTable(StoreTableRequest $request, Table $table): RedirectResponse
    {
        $table->update($request->validated());
        $this->audit($request, 'table.updated', 'table:'.$table->id);

        return back()->with('success', 'Mesa actualizada.');
    }

    public function assignWaiter(AssignTableWaiterRequest $request, Table $table): RedirectResponse
    {
        $employeeId = $request->validated('assigned_employee_id');
        if ($employeeId && ! Employee::query()->whereKey($employeeId)->where('position', 'waiter')->where('is_active', true)
            ->whereHas('user', fn ($query) => $query->where('role', 'waiter'))->exists()) {
            return back()->withErrors(['assigned_employee_id' => 'Selecciona un mozo activo con cuenta de mozo vinculada.']);
        }

        $previous = $table->assigned_employee_id;
        $table->update(['assigned_employee_id' => $employeeId]);
        $this->audit($request, 'table.waiter_assigned', 'table:'.$table->id, ['previous' => $previous, 'employee_id' => $employeeId]);

        return back()->with('success', $employeeId ? 'Mozo asignado.' : 'Mesa sin mozo asignado.');
    }

    public function toggle(Request $request, Table $table): RedirectResponse
    {
        if ($table->is_active && $table->status === 'occupied') {
            return back()->withErrors(['table' => 'No se puede retirar una mesa ocupada.']);
        }

        $table->update(['is_active' => ! $table->is_active]);
        $this->audit($request, 'table.availability_changed', 'table:'.$table->id, ['is_active' => $table->is_active]);

        return back()->with('success', 'Disponibilidad de la mesa actualizada.');
    }

    public function destroyTable(Request $request, Table $table): RedirectResponse
    {
        if ($table->orders()->exists() || $table->reservations()->exists()) {
            return back()->withErrors(['table' => 'La mesa tiene historial; retírala de servicio en lugar de eliminarla.']);
        }

        $table->delete();
        $this->audit($request, 'table.deleted', 'table:'.$table->id);

        return back()->with('success', 'Mesa eliminada.');
    }

    public function updatePositions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'positions' => ['required', 'array', 'max:200'],
            'positions.*.id' => ['required', 'integer', 'distinct', 'exists:tables,id'],
            'positions.*.x' => ['required', 'integer', 'min:0', 'max:2000'],
            'positions.*.y' => ['required', 'integer', 'min:0', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['positions'] as $position) {
                Table::whereKey($position['id'])->update(['x_pos' => $position['x'], 'y_pos' => $position['y']]);
            }
        });
        $this->audit($request, 'table.positions_updated', 'tables', ['count' => count($validated['positions'])]);

        return response()->json(['status' => 'success', 'message' => 'Mapa guardado correctamente.']);
    }

    private function audit(Request $request, string $action, string $target, array $metadata = []): void
    {
        SystemAudit::create([
            'user_id' => $request->user()->id, 'action' => $action, 'target' => $target,
            'status' => 'success', 'metadata' => $metadata,
        ]);
    }
}
