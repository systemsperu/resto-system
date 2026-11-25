@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div><h1 class="h2 fw-bold mb-1">Áreas y mesas</h1><p class="text-muted mb-0">Configura el aforo, la disponibilidad y el mozo responsable.</p></div>
        <div class="d-grid d-sm-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#areaModal">Nueva área</button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tableModal" @disabled($areas->isEmpty())>Nueva mesa</button>
            <button class="btn btn-success" type="button" id="btnSave">Guardar plano</button>
        </div>
    </div>

    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @forelse($areas as $area)
        <section class="card border-0 shadow-sm mb-4" aria-labelledby="area-title-{{ $area->id }}">
            <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between gap-2 py-3">
                <div><h2 id="area-title-{{ $area->id }}" class="h5 mb-1">{{ $area->name }}</h2><span class="text-muted">{{ $area->tables->count() }} mesas · {{ $area->tables->sum('seats') }} asientos</span></div>
                <form action="{{ route('tables.destroyArea', $area) }}" method="POST" onsubmit="return confirm('¿Eliminar esta área vacía?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Eliminar área</button></form>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    @forelse($area->tables as $table)
                        <div class="col-12 col-md-6 col-xl-4">
                            <article class="border rounded-3 p-3 h-100 {{ $table->is_active ? '' : 'bg-light opacity-75' }}">
                                <div class="d-flex justify-content-between gap-2 mb-3">
                                    <div><h3 class="h6 mb-1">{{ $table->name }}</h3><span class="badge {{ $table->status === 'available' ? 'text-bg-success' : ($table->status === 'reserved' ? 'text-bg-warning' : 'text-bg-danger') }}">{{ ['available'=>'Disponible','reserved'=>'Reservada','occupied'=>'Ocupada'][$table->status] ?? $table->status }}</span></div>
                                    <strong>{{ $table->seats }} asientos</strong>
                                </div>
                                <form action="{{ route('tables.assignWaiter', $table) }}" method="POST" class="mb-2">@csrf @method('PATCH')
                                    <label class="form-label" for="waiter-{{ $table->id }}">Mozo asignado</label>
                                    <div class="input-group"><select class="form-select" id="waiter-{{ $table->id }}" name="assigned_employee_id"><option value="">Sin asignar</option>@foreach($waiters as $waiter)<option value="{{ $waiter->id }}" @selected($table->assigned_employee_id === $waiter->id)>{{ $waiter->full_name }}</option>@endforeach</select><button class="btn btn-outline-primary">Asignar</button></div>
                                </form>
                                <div class="d-flex gap-2 mb-2"><form action="{{ route('tables.qr.rotate', $table) }}" method="POST" class="flex-grow-1">@csrf<button class="btn btn-outline-primary w-100">{{ $table->qr_token ? 'Renovar QR' : 'Generar QR' }}</button></form>@if($table->qr_token && !$table->qr_revoked_at)<a class="btn btn-outline-dark" href="{{ route('tables.qr.image',$table) }}" target="_blank" aria-label="Descargar QR de {{ $table->name }}"><i class="bi bi-qr-code"></i></a>@endif</div>
                                <div class="d-flex gap-2">
                                    <form action="{{ route('tables.toggle', $table) }}" method="POST" class="flex-grow-1">@csrf @method('PATCH')<button class="btn btn-outline-secondary w-100">{{ $table->is_active ? 'Retirar' : 'Habilitar' }}</button></form>
                                    <form action="{{ route('tables.destroyTable', $table) }}" method="POST" onsubmit="return confirm('¿Eliminar esta mesa sin historial?')">@csrf @method('DELETE')<button class="btn btn-outline-danger" aria-label="Eliminar {{ $table->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button></form>
                                </div>
                            </article>
                        </div>
                    @empty
                        <div class="col-12"><p class="text-muted mb-0">Esta área todavía no tiene mesas.</p></div>
                    @endforelse
                </div>

                @if($area->tables->isNotEmpty())
                    <details><summary class="btn btn-outline-secondary mb-3">Editar distribución visual</summary>
                        <p class="small text-muted">Arrastra con mouse o tacto. La lista anterior contiene las mismas operaciones.</p>
                        <div class="salon-canvas border rounded position-relative overflow-auto" style="height: min(60vh, 600px); min-height: 360px; touch-action: pan-x pan-y; background-image: radial-gradient(#dee2e6 1px, transparent 1px); background-size: 20px 20px;">
                            @foreach($area->tables as $table)<button type="button" class="draggable-table btn btn-light border position-absolute" data-id="{{ $table->id }}" style="width:100px;height:100px;left:{{ $table->x_pos }}px;top:{{ $table->y_pos }}px;touch-action:none"><strong>{{ $table->name }}</strong><small class="d-block">{{ $table->seats }} asientos</small></button>@endforeach
                        </div>
                    </details>
                @endif
            </div>
        </section>
    @empty
        <div class="alert alert-info">Crea la primera área para comenzar a organizar el salón.</div>
    @endforelse
</div>

<div class="modal fade" id="areaModal" tabindex="-1" aria-labelledby="areaModalTitle"><div class="modal-dialog"><form action="{{ route('tables.storeArea') }}" method="POST" class="modal-content">@csrf<div class="modal-header"><h2 class="modal-title h5" id="areaModalTitle">Nueva área</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body"><label for="area_name" class="form-label">Nombre</label><input id="area_name" name="name" class="form-control" maxlength="80" required></div><div class="modal-footer"><button class="btn btn-primary">Crear área</button></div></form></div></div>

<div class="modal fade" id="tableModal" tabindex="-1" aria-labelledby="tableModalTitle"><div class="modal-dialog"><form action="{{ route('tables.storeTable') }}" method="POST" class="modal-content">@csrf<div class="modal-header"><h2 class="modal-title h5" id="tableModalTitle">Nueva mesa</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div><div class="modal-body d-grid gap-3"><div><label for="table_name" class="form-label">Nombre</label><input id="table_name" name="name" class="form-control" maxlength="80" required></div><div><label for="table_area" class="form-label">Área</label><select id="table_area" name="area_id" class="form-select" required>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></div><div><label for="table_seats" class="form-label">Número de asientos</label><input id="table_seats" name="seats" type="number" min="1" max="50" value="4" class="form-control" required></div></div><div class="modal-footer"><button class="btn btn-primary">Crear mesa</button></div></form></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.draggable-table').forEach((element) => {
        element.addEventListener('pointerdown', (event) => {
            element.setPointerCapture(event.pointerId);
            const startX = event.clientX - element.offsetLeft;
            const startY = event.clientY - element.offsetTop;
            const move = (pointerEvent) => {
                element.style.left = `${Math.max(0, pointerEvent.clientX - startX)}px`;
                element.style.top = `${Math.max(0, pointerEvent.clientY - startY)}px`;
            };
            element.addEventListener('pointermove', move);
            element.addEventListener('pointerup', () => element.removeEventListener('pointermove', move), { once: true });
        });
    });

    document.getElementById('btnSave').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const positions = [...document.querySelectorAll('.draggable-table')].map((element) => ({ id: Number(element.dataset.id), x: parseInt(element.style.left, 10), y: parseInt(element.style.top, 10) }));
        button.disabled = true;
        try {
            const response = await fetch(@json(route('tables.updatePositions')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) }, body: JSON.stringify({ positions }) });
            if (!response.ok) throw new Error('No se pudo guardar el plano.');
            window.location.reload();
        } catch (error) { alert(error.message); button.disabled = false; }
    });
});
</script>
@endsection
