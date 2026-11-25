@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h1 class="h3 mb-1">Alojamiento</h1><p class="text-muted mb-0">Reservas, recepción, pagos y limpieza.</p></div>
        @if(Auth::user()->role === 'admin')<form method="GET" action="{{ route('lodging.report') }}" class="d-flex flex-wrap gap-2"><input type="date" name="from" value="{{ today()->startOfMonth()->toDateString() }}" class="form-control py-2" aria-label="Inicio del reporte" required><input type="date" name="to" value="{{ today()->endOfMonth()->toDateString() }}" class="form-control py-2" aria-label="Fin del reporte" required><button class="btn btn-outline-primary py-2">Descargar reporte</button></form>@endif
        <span class="badge text-bg-success fs-6">{{ $metrics['occupancy'] }}% ocupación actual</span>
    </div>

    <div class="row g-3 mb-4">
        @foreach(['Unidades activas' => $metrics['active_units'], 'Ocupadas' => $metrics['occupied_units'], 'Llegadas hoy' => $metrics['arrivals'], 'Salidas hoy' => $metrics['departures']] as $label => $value)
        <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-muted">{{ $label }}</small><div class="fs-3 fw-bold">{{ $value }}</div></div></div></div>
        @endforeach
        <div class="col-12"><div class="alert alert-light border mb-0"><strong>Ingresos registrados:</strong> S/ {{ number_format((float) $metrics['revenue'], 2) }}</div></div>
    </div>

    @if(Auth::user()->hasPermission('lodging.reservations.manage'))
    <div class="card mb-4"><div class="card-body"><h2 class="h5">Nueva reserva</h2>
        <form method="POST" action="{{ route('lodging.reservations.store') }}" class="row g-3">@csrf
            <div class="col-12 col-md-4"><label class="form-label" for="client_id">Cliente</label><select id="client_id" name="client_id" class="form-select py-2" required><option value="">Seleccionar</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }}</option>@endforeach</select></div>
            <div class="col-12 col-md-4"><label class="form-label" for="accommodation_unit_id">Unidad</label><select id="accommodation_unit_id" name="accommodation_unit_id" class="form-select py-2" required><option value="">Seleccionar</option>@foreach($units->where('is_active', true) as $unit)<option value="{{ $unit->id }}" @selected(old('accommodation_unit_id') == $unit->id)>{{ $unit->name }} · {{ $unit->capacity }} personas</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label" for="check_in">Entrada</label><input id="check_in" name="check_in" type="date" min="{{ today()->toDateString() }}" value="{{ old('check_in') }}" class="form-control py-2" required></div>
            <div class="col-6 col-md-2"><label class="form-label" for="check_out">Salida</label><input id="check_out" name="check_out" type="date" min="{{ today()->addDay()->toDateString() }}" value="{{ old('check_out') }}" class="form-control py-2" required></div>
            <div class="col-6 col-md-2"><label class="form-label" for="adults">Adultos</label><input id="adults" name="adults" type="number" min="1" max="30" value="{{ old('adults', 1) }}" class="form-control py-2" required></div>
            <div class="col-6 col-md-2"><label class="form-label" for="children">Niños</label><input id="children" name="children" type="number" min="0" max="30" value="{{ old('children', 0) }}" class="form-control py-2"></div>
            <div class="col-12 col-md-6"><label class="form-label" for="reservation_notes">Notas</label><input id="reservation_notes" name="notes" value="{{ old('notes') }}" class="form-control py-2" maxlength="1000"></div>
            <div class="col-12 col-md-2 d-grid align-items-end"><button class="btn btn-primary py-2">Confirmar reserva</button></div>
        </form>
    </div></div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8"><h2 class="h4">Reservas recientes</h2>
            <div class="row g-3">
            @forelse($reservations as $reservation)
                <div class="col-12"><article class="card"><div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2"><div><strong>{{ $reservation->unit->name }}</strong> · {{ $reservation->client->name }}<div class="small text-muted">{{ $reservation->check_in->format('d/m/Y') }} → {{ $reservation->check_out->format('d/m/Y') }} · {{ $reservation->adults + $reservation->children }} huéspedes</div></div><span class="badge text-bg-{{ $reservation->status === 'checked_in' ? 'success' : ($reservation->status === 'cancelled' ? 'secondary' : 'primary') }} align-self-start">{{ str_replace('_', ' ', $reservation->status) }}</span></div>
                    <div class="row g-2 mt-2"><div class="col-6 col-md-3"><small>Total</small><div class="fw-bold">S/ {{ number_format((float) $reservation->total_amount, 2) }}</div></div><div class="col-6 col-md-3"><small>Pagado</small><div>S/ {{ number_format($reservation->paid_amount, 2) }}</div></div><div class="col-6 col-md-3"><small>Saldo</small><div>S/ {{ number_format($reservation->balance, 2) }}</div></div><div class="col-6 col-md-3"><small>Huéspedes</small><div>{{ $reservation->guests->count() }}</div></div></div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @if(Auth::user()->hasPermission('lodging.checkin.manage') && $reservation->status === 'confirmed')
                        <form method="POST" action="{{ route('lodging.reservations.transition', $reservation) }}">@csrf<input type="hidden" name="status" value="checked_in"><button class="btn btn-success py-2">Registrar entrada</button></form>
                        <form method="POST" action="{{ route('lodging.reservations.transition', $reservation) }}">@csrf<input type="hidden" name="status" value="cancelled"><button class="btn btn-outline-danger py-2">Cancelar</button></form>
                        @elseif(Auth::user()->hasPermission('lodging.checkin.manage') && $reservation->status === 'checked_in')
                        <form method="POST" action="{{ route('lodging.reservations.transition', $reservation) }}">@csrf<input type="hidden" name="status" value="checked_out"><button class="btn btn-primary py-2">Registrar salida</button></form>
                        @endif
                    </div>
                    @if(Auth::user()->hasPermission('lodging.payments.manage') && $reservation->balance > 0 && $reservation->status !== 'cancelled')
                    <form method="POST" action="{{ route('lodging.payments.store', $reservation) }}" class="row g-2 mt-2 border-top pt-3">@csrf<div class="col-6 col-md-3"><select name="method" class="form-select py-2" aria-label="Medio de pago"><option value="cash">Efectivo</option><option value="card">Tarjeta</option><option value="transfer">Transferencia</option><option value="yape">Yape</option><option value="plin">Plin</option></select></div><div class="col-6 col-md-3"><input name="amount" type="number" min="0.01" max="{{ $reservation->balance }}" step="0.01" class="form-control py-2" placeholder="Monto" required></div><div class="col-12 col-md-3"><input name="reference" class="form-control py-2" placeholder="Referencia opcional"></div><div class="col-12 col-md-3 d-grid"><button class="btn btn-outline-primary py-2">Registrar pago</button></div></form>
                    @endif
                    @if(Auth::user()->hasPermission('lodging.guests.manage') && !in_array($reservation->status, ['cancelled', 'checked_out']))
                    <form method="POST" action="{{ route('lodging.guests.store', $reservation) }}" class="row g-2 mt-2">@csrf<div class="col-12 col-md-5"><input name="full_name" class="form-control py-2" placeholder="Nombre del huésped" required></div><div class="col-5 col-md-2"><select name="document_type" class="form-select py-2"><option value="">Documento</option><option>DNI</option><option>CE</option><option value="PASSPORT">Pasaporte</option></select></div><div class="col-7 col-md-3"><input name="document_number" class="form-control py-2" placeholder="Número"></div><div class="col-12 col-md-2 d-grid"><button class="btn btn-outline-secondary py-2">Agregar huésped</button></div></form>
                    @endif
                </div></article></div>
            @empty
                <div class="col-12"><div class="alert alert-light border">Todavía no existen reservas de alojamiento.</div></div>
            @endforelse
            </div><div class="mt-3">{{ $reservations->links() }}</div>
        </div>
        <div class="col-12 col-xl-4"><h2 class="h4">Limpieza y preparación</h2>
            @forelse($tasks as $task)<div class="card mb-3"><div class="card-body"><div class="d-flex justify-content-between"><strong>{{ $task->unit->name }}</strong><span class="badge text-bg-warning">{{ $task->status }}</span></div><div class="small text-muted mb-3">Programada: {{ $task->scheduled_for->format('d/m/Y') }}</div>@if(Auth::user()->hasPermission('lodging.cleaning.manage'))<form method="POST" action="{{ route('lodging.housekeeping.complete', $task) }}">@csrf<button class="btn btn-success w-100 py-2">Marcar limpieza completa</button></form>@endif</div></div>@empty<div class="alert alert-light border">No hay tareas pendientes.</div>@endforelse
        </div>
    </div>

    @if(Auth::user()->role === 'admin')
    <h2 class="h4 mt-5">Configuración de alojamiento</h2>
    <div class="row g-3">
        <div class="col-12 col-lg-4"><div class="card h-100"><div class="card-body"><h3 class="h5">Tipo de unidad</h3><form method="POST" action="{{ route('lodging.types.store') }}">@csrf<input name="name" class="form-control py-2 mb-2" placeholder="Ej. Búngalo familiar" required><textarea name="description" class="form-control mb-2" placeholder="Descripción"></textarea><div class="row g-2"><div class="col-6"><input name="base_capacity" type="number" min="1" max="30" value="2" class="form-control py-2" aria-label="Capacidad base" required></div><div class="col-6"><input name="max_capacity" type="number" min="1" max="30" value="4" class="form-control py-2" aria-label="Capacidad máxima" required></div></div><input name="amenities" class="form-control py-2 my-2" placeholder="Servicios separados por coma"><button class="btn btn-primary w-100 py-2">Crear tipo</button></form></div></div></div>
        <div class="col-12 col-lg-4"><div class="card h-100"><div class="card-body"><h3 class="h5">Unidad</h3><form method="POST" action="{{ route('lodging.units.store') }}">@csrf<select name="accommodation_type_id" class="form-select py-2 mb-2" required><option value="">Tipo</option>@foreach($types as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select><input name="name" class="form-control py-2 mb-2" placeholder="Nombre" required><input name="code" class="form-control py-2 mb-2" placeholder="Código" required><input name="capacity" type="number" min="1" max="30" class="form-control py-2 mb-2" placeholder="Capacidad" required><input name="location" class="form-control py-2 mb-2" placeholder="Ubicación"><input name="amenities" class="form-control py-2 mb-2" placeholder="Servicios separados por coma"><input name="media" class="form-control py-2 mb-2" placeholder="URLs de imágenes separadas por coma"><button class="btn btn-primary w-100 py-2">Crear unidad</button></form></div></div></div>
        <div class="col-12 col-lg-4"><div class="card h-100"><div class="card-body"><h3 class="h5">Tarifa y temporada</h3><form method="POST" action="{{ route('lodging.rates.store') }}">@csrf<select name="accommodation_type_id" class="form-select py-2 mb-2" required><option value="">Tipo</option>@foreach($types as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select><input name="name" class="form-control py-2 mb-2" placeholder="Temporada" required><div class="row g-2 mb-2"><div class="col-6"><input name="starts_on" type="date" class="form-control py-2" required></div><div class="col-6"><input name="ends_on" type="date" class="form-control py-2" required></div></div><input name="nightly_rate" type="number" min="0.01" step="0.01" class="form-control py-2 mb-2" placeholder="Tarifa por noche" required><input name="minimum_nights" type="number" min="1" max="90" value="1" class="form-control py-2 mb-2" aria-label="Noches mínimas" required><label class="form-label" for="weekdays">Días aplicables; vacío significa todos</label><select id="weekdays" name="weekdays[]" class="form-select mb-2" multiple size="3">@foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $day=>$label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select><button class="btn btn-primary w-100 py-2">Crear tarifa</button></form></div></div></div>
    </div>
    <div class="card mt-3"><div class="card-body"><h3 class="h5">Bloquear unidad por mantenimiento u otro motivo</h3><div class="row g-3">@foreach($units as $unit)<div class="col-12 col-md-6 col-xl-4"><form method="POST" action="{{ route('lodging.blocks.store', $unit) }}" class="border rounded p-3">@csrf<strong>{{ $unit->name }}</strong><select name="reason" class="form-select py-2 my-2"><option value="maintenance">Mantenimiento</option><option value="owner_use">Uso interno</option><option value="other">Otro</option></select><div class="row g-2"><div class="col-6"><input name="starts_on" type="date" class="form-control py-2" required></div><div class="col-6"><input name="ends_on" type="date" class="form-control py-2" required></div></div><input name="notes" class="form-control py-2 my-2" placeholder="Motivo"><button class="btn btn-outline-danger w-100 py-2">Bloquear</button></form></div>@endforeach</div></div></div>
    @if($blocks->isNotEmpty())<div class="card mt-3"><div class="card-body"><h3 class="h5">Bloqueos activos</h3><div class="row g-2">@foreach($blocks as $block)<div class="col-12 col-md-6"><div class="border rounded p-3 d-flex justify-content-between align-items-center gap-2"><div><strong>{{ $block->unit->name }}</strong><div class="small text-muted">{{ $block->starts_on->format('d/m/Y') }} → {{ $block->ends_on->format('d/m/Y') }} · {{ $block->reason }}</div></div><form method="POST" action="{{ route('lodging.blocks.release', $block) }}">@csrf<button class="btn btn-outline-success py-2">Liberar</button></form></div></div>@endforeach</div></div></div>@endif
    @endif
</div>
@endsection
