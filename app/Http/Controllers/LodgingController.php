<?php

namespace App\Http\Controllers;

use App\Models\AccommodationBlock;
use App\Models\AccommodationRate;
use App\Models\AccommodationType;
use App\Models\AccommodationUnit;
use App\Models\Client;
use App\Models\HousekeepingTask;
use App\Models\LodgingReservation;
use App\Models\Payment;
use App\Services\Lodging\AvailabilityService;
use App\Services\Lodging\LodgingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LodgingController extends Controller
{
    public function index()
    {
        $types = AccommodationType::withCount('units')->orderBy('name')->get();
        $units = AccommodationUnit::with('type')->orderBy('name')->get();
        $rates = AccommodationRate::with('type')->latest('starts_on')->get();
        $clients = Client::orderBy('name')->get(['id', 'name']);
        $reservations = LodgingReservation::with(['client', 'unit', 'payments', 'guests'])->latest()->paginate(30);
        $tasks = HousekeepingTask::with(['unit', 'assignedEmployee'])->where('status', '!=', 'completed')->orderBy('scheduled_for')->get();
        $blocks = AccommodationBlock::with('unit')->whereNull('released_at')->orderBy('starts_on')->get();
        $today = today();
        $occupied = AccommodationUnit::where('status', 'occupied')->count();
        $activeUnits = AccommodationUnit::where('is_active', true)->count();
        $metrics = [
            'active_units' => $activeUnits,
            'occupied_units' => $occupied,
            'occupancy' => $activeUnits ? round($occupied * 100 / $activeUnits, 1) : 0,
            'arrivals' => LodgingReservation::whereDate('check_in', $today)->where('status', 'confirmed')->count(),
            'departures' => LodgingReservation::whereDate('check_out', $today)->where('status', 'checked_in')->count(),
            'revenue' => Payment::where('source', 'lodging')->where('status', 'confirmed')->sum('amount'),
        ];

        return view('lodging.index', compact('types', 'units', 'rates', 'clients', 'reservations', 'tasks', 'blocks', 'metrics'));
    }

    public function availability(Request $request, AvailabilityService $service)
    {
        $data = $request->validate(['check_in' => ['required', 'date', 'after_or_equal:today'], 'check_out' => ['required', 'date', 'after:check_in'], 'guests' => ['required', 'integer', 'min:1', 'max:30']]);
        if (CarbonImmutable::parse($data['check_in'])->diffInDays(CarbonImmutable::parse($data['check_out'])) > 90) {
            throw ValidationException::withMessages(['check_out' => 'La estadía no puede superar 90 noches.']);
        }
        $units = $service->available(CarbonImmutable::parse($data['check_in']), CarbonImmutable::parse($data['check_out']), (int) $data['guests']);

        return response()->json(['data' => $units->map(fn ($unit) => ['id' => $unit->id, 'name' => $unit->name, 'type' => $unit->type->name, 'capacity' => $unit->capacity, 'nights' => $unit->quote_nights, 'nightly_rate' => $unit->quote_nightly_rate, 'total' => $unit->quote_total])]);
    }

    public function report(Request $request)
    {
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);
        if (CarbonImmutable::parse($data['to'])->gt(CarbonImmutable::parse($data['from'])->addYear())) {
            throw ValidationException::withMessages(['to' => 'El reporte no puede abarcar más de un año.']);
        }
        $reservations = LodgingReservation::with(['unit', 'payments'])
            ->where('check_in', '<=', $data['to'])
            ->where('check_out', '>=', $data['from'])
            ->orderBy('check_in')->get();

        return response()->streamDownload(function () use ($reservations) {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Reserva', 'Unidad', 'Entrada', 'Salida', 'Noches', 'Estado', 'Total', 'Pagado', 'Saldo']);
            foreach ($reservations as $reservation) {
                fputcsv($output, [$reservation->public_id, $this->safeCsv($reservation->unit->name), $reservation->check_in->toDateString(), $reservation->check_out->toDateString(), $reservation->check_in->diffInDays($reservation->check_out), $reservation->status, $reservation->total_amount, $reservation->paid_amount, $reservation->balance]);
            }
            fclose($output);
        }, 'alojamiento-'.$data['from'].'-'.$data['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function storeType(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:accommodation_types'], 'description' => ['nullable', 'string', 'max:1000'], 'base_capacity' => ['required', 'integer', 'min:1', 'max:30'], 'max_capacity' => ['required', 'integer', 'gte:base_capacity', 'max:30'], 'amenities' => ['nullable', 'string', 'max:500']]);
        $data['amenities'] = $this->list($data['amenities'] ?? null);
        AccommodationType::create($data);

        return back()->with('success', 'Tipo de alojamiento creado.');
    }

    public function storeUnit(Request $request)
    {
        $data = $request->validate(['accommodation_type_id' => ['required', 'exists:accommodation_types,id'], 'name' => ['required', 'string', 'max:100', 'unique:accommodation_units'], 'code' => ['required', 'alpha_dash', 'max:30', 'unique:accommodation_units'], 'capacity' => ['required', 'integer', 'min:1', 'max:30'], 'location' => ['nullable', 'string', 'max:150'], 'amenities' => ['nullable', 'string', 'max:500'], 'media' => ['nullable', 'string', 'max:2000']]);
        $type = AccommodationType::findOrFail($data['accommodation_type_id']);
        abort_if($data['capacity'] > $type->max_capacity, 422, 'La capacidad supera el máximo del tipo.');
        $data['code'] = strtoupper($data['code']);
        $data['amenities'] = $this->list($data['amenities'] ?? null);
        $data['media'] = $this->list($data['media'] ?? null);
        AccommodationUnit::create($data);

        return back()->with('success', 'Unidad creada.');
    }

    public function storeRate(Request $request)
    {
        $data = $request->validate(['accommodation_type_id' => ['required', 'exists:accommodation_types,id'], 'name' => ['required', 'string', 'max:100'], 'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'], 'nightly_rate' => ['required', 'numeric', 'gt:0'], 'minimum_nights' => ['required', 'integer', 'min:1', 'max:90'], 'weekdays' => ['nullable', 'array'], 'weekdays.*' => ['integer', 'between:1,7']]);
        AccommodationRate::create($data);

        return back()->with('success', 'Tarifa creada.');
    }

    public function storeReservation(Request $request, LodgingService $service)
    {
        $data = $request->validate(['client_id' => ['required', 'exists:clients,id'], 'accommodation_unit_id' => ['required', 'exists:accommodation_units,id'], 'check_in' => ['required', 'date', 'after_or_equal:today'], 'check_out' => ['required', 'date', 'after:check_in'], 'adults' => ['required', 'integer', 'min:1', 'max:30'], 'children' => ['nullable', 'integer', 'min:0', 'max:30'], 'notes' => ['nullable', 'string', 'max:1000']]);
        if (CarbonImmutable::parse($data['check_in'])->diffInDays(CarbonImmutable::parse($data['check_out'])) > 90) {
            throw ValidationException::withMessages(['check_out' => 'La estadía no puede superar 90 noches.']);
        }
        try {
            $reservation = $service->reserve($data, $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['reservation' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Reserva '.$reservation->public_id.' confirmada.');
    }

    public function transition(Request $request, LodgingReservation $reservation, LodgingService $service)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['checked_in', 'checked_out', 'cancelled'])]]);
        try {
            $service->transition($reservation, $data['status'], $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return back()->with('success', 'Estado de la reserva actualizado.');
    }

    public function storeGuest(Request $request, LodgingReservation $reservation)
    {
        $data = $request->validate(['full_name' => ['required', 'string', 'max:150'], 'document_type' => ['nullable', Rule::in(['DNI', 'CE', 'PASSPORT'])], 'document_number' => ['nullable', 'string', 'max:30'], 'nationality' => ['nullable', 'string', 'max:60']]);
        abort_if($reservation->guests()->count() >= $reservation->adults + $reservation->children, 422, 'Se alcanzó la capacidad declarada en la reserva.');
        $reservation->guests()->create($data);

        return back()->with('success', 'Huésped agregado.');
    }

    public function storePayment(Request $request, LodgingReservation $reservation, LodgingService $service)
    {
        $data = $request->validate(['method' => ['required', Rule::in(['cash', 'card', 'transfer', 'yape', 'plin'])], 'amount' => ['required', 'numeric', 'gt:0'], 'reference' => ['nullable', 'string', 'max:100']]);
        try {
            $service->addPayment($reservation, $data, $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return back()->with('success', 'Pago de alojamiento registrado.');
    }

    public function storeBlock(Request $request, AccommodationUnit $unit, LodgingService $service)
    {
        $data = $request->validate(['starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after:starts_on'], 'reason' => ['required', Rule::in(['maintenance', 'owner_use', 'other'])], 'notes' => ['nullable', 'string', 'max:1000']]);
        try {
            $service->block($unit, $data, $request->user());
        } catch (\DomainException $exception) {
            return back()->withErrors(['block' => $exception->getMessage()]);
        }

        return back()->with('success', 'Unidad bloqueada.');
    }

    public function completeCleaning(Request $request, HousekeepingTask $task, LodgingService $service)
    {
        $service->completeCleaning($task, $request->user());

        return back()->with('success', 'Limpieza completada y unidad actualizada.');
    }

    public function releaseBlock(Request $request, AccommodationBlock $block, LodgingService $service)
    {
        $service->releaseBlock($block, $request->user());

        return back()->with('success', 'Bloqueo liberado.');
    }

    private function list(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    private function safeCsv(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
