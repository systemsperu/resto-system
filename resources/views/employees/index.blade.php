@extends('layouts.app')

@section('content')
<div class="container-fluid app-page">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">Personal y turnos</h1>
            <p class="text-muted mb-0">La ficha laboral es independiente de la cuenta de acceso.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeCreate">Agregar personal</button>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Revisa los datos.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3">
        @forelse($employees as $employee)
            <div class="col-12 col-xl-6">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <h2 class="h5 mb-1">{{ $employee->full_name }}</h2>
                                <p class="text-muted mb-1">{{ ucfirst($employee->position) }}</p>
                                <span class="badge {{ $employee->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $employee->is_active ? 'Activo' : 'Inactivo' }}</span>
                            </div>
                            <form method="POST" action="{{ route('employees.toggle', $employee) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary">{{ $employee->is_active ? 'Desactivar' : 'Activar' }}</button></form>
                        </div>
                        <dl class="row small mt-3 mb-3">
                            <dt class="col-4">Acceso</dt><dd class="col-8">{{ $employee->user?->email ?? 'Sin cuenta vinculada' }}</dd>
                            <dt class="col-4">Teléfono</dt><dd class="col-8">{{ $employee->phone ?: 'No registrado' }}</dd>
                        </dl>
                        <h3 class="h6">Próximos turnos</h3>
                        <div class="vstack gap-2 mb-3">
                            @forelse($employee->shifts->where('status', 'scheduled')->take(3) as $shift)
                                <div class="border rounded p-2 d-flex justify-content-between align-items-center gap-2">
                                    <span>{{ $shift->starts_at->format('d/m H:i') }} – {{ $shift->ends_at->format('d/m H:i') }}</span>
                                    <form method="POST" action="{{ route('work_shifts.cancel', $shift) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger" aria-label="Cancelar turno de {{ $employee->full_name }}">Cancelar</button></form>
                                </div>
                            @empty <p class="text-muted small mb-0">Sin turnos programados.</p> @endforelse
                        </div>
                        @if($employee->is_active)
                            <form method="POST" action="{{ route('employees.shifts.store', $employee) }}" class="row g-2">@csrf
                                <div class="col-12 col-sm-5"><label class="form-label" for="starts_{{ $employee->id }}">Inicio</label><input id="starts_{{ $employee->id }}" name="starts_at" type="datetime-local" class="form-control" required></div>
                                <div class="col-12 col-sm-5"><label class="form-label" for="ends_{{ $employee->id }}">Fin</label><input id="ends_{{ $employee->id }}" name="ends_at" type="datetime-local" class="form-control" required></div>
                                <input type="hidden" name="status" value="scheduled">
                                <div class="col-12 col-sm-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Añadir</button></div>
                            </form>
                        @endif
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-info">Aún no hay fichas de personal.</div></div>
        @endforelse
    </div>
</div>

<div class="modal fade" id="employeeCreate" tabindex="-1" aria-labelledby="employeeCreateTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('employees.store') }}" class="modal-content">@csrf
        <div class="modal-header"><h2 class="modal-title h5" id="employeeCreateTitle">Agregar personal</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body vstack gap-3">
            <div><label for="employee_name" class="form-label">Nombre completo</label><input id="employee_name" name="full_name" class="form-control" required></div>
            <div><label for="employee_position" class="form-label">Cargo</label><select id="employee_position" name="position" class="form-select" required>@foreach(['manager'=>'Gerencia','cashier'=>'Caja','waiter'=>'Mozo','kitchen'=>'Cocina','warehouse'=>'Almacén','reception'=>'Recepción','cleaning'=>'Limpieza','marketing'=>'Marketing','other'=>'Otro'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div><label for="employee_phone" class="form-label">Teléfono</label><input id="employee_phone" name="phone" class="form-control" inputmode="tel"></div>
            <div><label for="employee_user" class="form-label">Cuenta de acceso opcional</label><select id="employee_user" name="user_id" class="form-select"><option value="">Sin cuenta</option>@foreach($availableUsers as $user)<option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>@endforeach</select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div>
    </form></div>
</div>
@endsection
