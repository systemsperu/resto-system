<?php

namespace App\Http\Controllers;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\SystemAudit;
use App\Services\DatabaseMaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class SystemController extends Controller
{
    public function __construct(private readonly DatabaseMaintenanceService $maintenance)
    {
    }

    public function index()
    {
        $counts = [
            'orders' => Order::count(),
            'reservations' => Reservation::count(),
            'logs' => InventoryLog::count(),
        ];
        $databaseName = $this->maintenance->databaseName();
        $maintenanceEnabled = (bool) config('maintenance.enabled');

        return view('system.index', compact('counts', 'databaseName', 'maintenanceEnabled'));
    }

    public function resetData(Request $request)
    {
        $database = $this->maintenance->databaseName();
        $this->validateConfirmation($request, 'REINICIAR '.$database);

        try {
            $this->maintenance->assertSafeTarget();
            $backup = $this->maintenance->createBackup();

            DB::transaction(function (): void {
                DB::table('deliveries')->delete();
                DB::table('credit_notes')->delete();
                DB::table('order_details')->delete();
                DB::table('orders')->delete();
                DB::table('reservations')->delete();
                DB::table('expenses')->delete();
                DB::table('cash_registers')->delete();
                DB::table('inventory_logs')->delete();
                DB::table('daily_summaries')->delete();
                DB::table('products')->update(['stock' => 0]);
            });

            $this->audit($request, 'reset', $database, 'success', ['backup' => basename($backup)]);

            return back()->with('success', 'Datos operativos reiniciados. Se creó un respaldo privado previo.');
        } catch (Throwable $exception) {
            $this->audit($request, 'reset', $database, 'failed');
            Log::error('Falló el reinicio controlado del sistema.', ['exception' => $exception]);

            return back()->with('error', 'No se realizó el reinicio. Revisa la configuración y los registros del sistema.');
        }
    }

    public function backup(Request $request)
    {
        $database = $this->maintenance->databaseName();
        $this->validateConfirmation($request, 'RESPALDAR '.$database);

        try {
            $path = $this->maintenance->createBackup();
            $this->audit($request, 'backup', $database, 'success', ['file' => basename($path)]);

            return response()->download($path, basename($path))->deleteFileAfterSend(true);
        } catch (Throwable $exception) {
            $this->audit($request, 'backup', $database, 'failed');
            Log::error('Falló la generación del respaldo.', ['exception' => $exception]);

            return back()->with('error', 'No se pudo generar el respaldo de forma segura.');
        }
    }

    public function restore(Request $request)
    {
        $database = $this->maintenance->databaseName();
        $request->validate([
            'backup_file' => [
                'required',
                'file',
                'extensions:sql',
                'max:'.config('maintenance.max_restore_kb'),
            ],
            'password' => ['required', 'string'],
            'confirmation' => ['required', Rule::in(['RESTAURAR '.$database])],
        ]);
        $this->ensureCurrentPassword($request);

        try {
            $this->maintenance->assertSafeTarget();
            $uploadedPath = $request->file('backup_file')->getRealPath();
            $this->maintenance->assertSqlBackup($uploadedPath);
            $safetyBackup = $this->maintenance->createBackup();
            $this->maintenance->restore($uploadedPath);
            $this->audit($request, 'restore', $database, 'success', ['safety_backup' => basename($safetyBackup)]);

            return back()->with('success', 'Restauración completada. Se conservó un respaldo privado del estado anterior.');
        } catch (Throwable $exception) {
            $this->audit($request, 'restore', $database, 'failed');
            Log::error('Falló la restauración controlada.', ['exception' => $exception]);

            return back()->with('error', 'No se restauró la base de datos. El archivo o el destino no superó las validaciones.');
        }
    }

    private function validateConfirmation(Request $request, string $expected): void
    {
        $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', Rule::in([$expected])],
        ]);
        $this->ensureCurrentPassword($request);
    }

    private function ensureCurrentPassword(Request $request): void
    {
        if (!Hash::check((string) $request->password, $request->user()->password)) {
            abort(422, 'La contraseña actual no es válida.');
        }
    }

    private function audit(Request $request, string $action, string $target, string $status, array $metadata = []): void
    {
        SystemAudit::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'target' => $target,
            'status' => $status,
            'metadata' => $metadata + ['ip' => $request->ip()],
        ]);
    }
}
