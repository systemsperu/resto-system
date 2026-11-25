@extends('layouts.app')

@section('content')
@php
    $moduleIcons = [
        'restaurant' => 'shop',
        'inventory' => 'boxes',
        'reservations' => 'calendar-check',
        'delivery' => 'bicycle',
        'reports' => 'bar-chart-line',
        'sunat' => 'receipt-cutoff',
        'landing' => 'window',
        'lodging' => 'houses',
        'marketing' => 'megaphone',
    ];
    $moduleDescriptions = [
        'restaurant' => 'Mesas, punto de venta, cocina, caja, carta y ventas del restaurante.',
        'inventory' => 'Productos, existencias, movimientos y alertas de inventario.',
        'reservations' => 'Agenda y seguimiento de reservas de mesas.',
        'delivery' => 'Pedidos para reparto, asignación de conductores y seguimiento.',
        'reports' => 'Indicadores y reportes operativos para administradores.',
        'sunat' => 'Comprobantes, notas de crédito y resúmenes electrónicos.',
        'landing' => 'Sitio público administrable para presentar el negocio.',
        'lodging' => 'Búngalos, disponibilidad, estadías, entradas, salidas y limpieza.',
        'marketing' => 'Campañas, cupones y fidelización de clientes.',
    ];
@endphp
<div class="modules-page">
    <header class="modules-header">
        <div>
            <p class="modules-eyebrow">Administración</p>
            <h1>Módulos del sistema</h1>
            <p>Activa solo las áreas que utiliza este negocio. Los permisos de cada usuario se administran por separado.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary modules-back">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <span>Configuración</span>
        </a>
    </header>

    @if($errors->any())
        <div class="alert alert-danger" id="module-errors" role="alert" tabindex="-1">
            <h2 class="h6 fw-bold">No se pudo actualizar el módulo</h2>
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="alert modules-security" role="note">
        <i class="bi bi-shield-lock" aria-hidden="true"></i>
        <div><strong>Visibilidad no significa autorización.</strong> Ocultar un módulo simplifica la interfaz; las rutas y acciones continúan protegidas en el servidor.</div>
    </div>

    <div class="modules-grid">
        @forelse($modules as $key => $module)
            @php
                $moduleKey = is_string($key) ? $key : data_get($module, 'key');
                $name = data_get($module, 'name', str($moduleKey)->headline());
                $description = data_get($module, 'description');
                $description = filled($description) ? $description : ($moduleDescriptions[$moduleKey] ?? 'Funciones opcionales de esta instalación.');
                $enabled = (bool) data_get($module, 'enabled', false);
                $required = (bool) data_get($module, 'required', false);
                $dependencies = data_get($module, 'dependencies', []);
                $missingDependencies = data_get($module, 'missing_dependencies');
                $missingDependencies = is_array($missingDependencies)
                    ? $missingDependencies
                    : array_values(array_filter($dependencies, fn ($dependency) => !(bool) data_get($modules, $dependency.'.enabled', false)));
                $available = (bool) data_get($module, 'available', true) && $missingDependencies === [];
                $enabledDependents = array_values(array_filter(
                    data_get($module, 'dependents', []),
                    fn ($dependent) => (bool) data_get($modules, $dependent.'.enabled', false)
                ));
                $reason = data_get($module, 'status_reason');
                $canToggle = !$required && ($available || $enabled) && !($enabled && $enabledDependents !== []);
            @endphp
            <article class="module-card {{ $enabled ? 'is-enabled' : '' }} {{ !$available ? 'is-blocked' : '' }}">
                <div class="module-card__top">
                    <div class="module-card__icon" aria-hidden="true">
                        <i class="bi bi-{{ $moduleIcons[$moduleKey] ?? 'grid' }}"></i>
                    </div>
                    <span class="module-status {{ $enabled ? 'is-on' : ($available ? 'is-off' : 'is-blocked') }}">
                        @if($required)Esencial @elseif(!$available)Requiere configuración @elseif($enabled)Activo @elseInactivo @endif
                    </span>
                </div>

                <h2>{{ $name }}</h2>
                <p class="module-card__description">{{ $description }}</p>

                @if($dependencies !== [])
                    <div class="module-dependencies">
                        <strong>Depende de:</strong>
                        <div class="module-chips">
                            @foreach($dependencies as $dependency)
                                @php
                                    $dependencyKey = is_array($dependency) ? data_get($dependency, 'key') : $dependency;
                                    $dependencyName = is_array($dependency) ? data_get($dependency, 'name', str($dependencyKey)->headline()) : str($dependencyKey)->headline();
                                    $missing = in_array($dependencyKey, $missingDependencies, true);
                                @endphp
                                <span class="module-chip {{ $missing ? 'is-missing' : '' }}">
                                    <i class="bi bi-{{ $missing ? 'exclamation-circle' : 'check-circle' }}" aria-hidden="true"></i>
                                    {{ $dependencyName }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($reason)
                    <p class="module-reason"><i class="bi bi-info-circle" aria-hidden="true"></i> {{ $reason }}</p>
                @elseif(!$available && $missingDependencies !== [])
                    <p class="module-reason"><i class="bi bi-info-circle" aria-hidden="true"></i> Activa primero los módulos requeridos.</p>
                @elseif($enabled && $enabledDependents !== [])
                    <p class="module-reason"><i class="bi bi-diagram-3" aria-hidden="true"></i> Para deshabilitarlo, desactiva antes: {{ collect($enabledDependents)->map(fn ($dependent) => data_get($modules, $dependent.'.name', str($dependent)->headline()))->join(', ') }}.</p>
                @endif

                <form method="POST" action="{{ route('modules.update', $moduleKey) }}" class="module-action">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="enabled" value="0">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="module-{{ $moduleKey }}" name="enabled" value="1" @checked($enabled) @disabled(!$canToggle)>
                        <label class="form-check-label fw-bold" for="module-{{ $moduleKey }}">
                            {{ $enabled ? 'Módulo habilitado' : 'Habilitar módulo' }}
                        </label>
                    </div>
                    @if($canToggle)
                        <button type="submit" class="btn {{ $enabled ? 'btn-outline-secondary' : 'btn-primary' }}">
                            {{ $enabled ? 'Deshabilitar' : 'Habilitar' }}
                        </button>
                    @endif
                </form>
            </article>
        @empty
            <div class="modules-empty">
                <i class="bi bi-grid" aria-hidden="true"></i>
                <h2>No hay módulos configurables</h2>
                <p>El registro central no devolvió opciones para esta instalación.</p>
            </div>
        @endforelse
    </div>
</div>

<style>
    .modules-page { width: min(100%, 1120px); margin: 0 auto; padding-bottom: 32px; }
    .modules-header { display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px; }
    .modules-header h1 { margin: 0; color: var(--text-main); font-size: clamp(1.55rem, 7vw, 2.1rem); font-weight: 800; letter-spacing: -.03em; }
    .modules-header p:not(.modules-eyebrow) { max-width: 680px; margin: 7px 0 0; color: var(--text-muted); line-height: 1.55; }
    .modules-eyebrow { margin: 0 0 4px; color: var(--primary); font-size: .75rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .modules-back { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; align-self: stretch; }
    .modules-security { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 20px; border: 1px solid #bae6fd; border-radius: 14px; color: #0c4a6e; background: #f0f9ff; }
    .modules-security > i { margin-top: 2px; font-size: 1.2rem; }
    .modules-grid { display: grid; gap: 16px; }
    .module-card { min-width: 0; padding: 18px; border: 1px solid #dfe3ec; border-radius: 16px; background: #fff; box-shadow: 0 4px 18px rgba(30,30,46,.05); }
    .module-card.is-enabled { border-top: 4px solid var(--primary); padding-top: 15px; }
    .module-card.is-blocked { background: #f8fafc; }
    .module-card__top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .module-card__icon { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 14px; color: #fff; background: var(--primary); font-size: 1.25rem; }
    .module-card h2 { margin: 0; font-size: 1.1rem; font-weight: 800; }
    .module-card__description { min-height: 3em; margin: 7px 0 16px; color: var(--text-muted); line-height: 1.5; }
    .module-status { padding: 6px 10px; border-radius: 999px; font-size: .72rem; font-weight: 800; white-space: nowrap; }
    .module-status.is-on { color: #166534; background: #dcfce7; }
    .module-status.is-off { color: #475569; background: #e2e8f0; }
    .module-status.is-blocked { color: #92400e; background: #fef3c7; }
    .module-dependencies { padding: 12px; border-radius: 12px; background: #f8fafc; color: var(--text-muted); font-size: .8rem; }
    .module-chips { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 8px; }
    .module-chip { padding: 5px 8px; border: 1px solid #bbf7d0; border-radius: 999px; color: #166534; background: #f0fdf4; font-weight: 700; }
    .module-chip.is-missing { border-color: #fde68a; color: #92400e; background: #fffbeb; }
    .module-reason { display: flex; gap: 7px; margin: 12px 0 0; color: #92400e; font-size: .82rem; line-height: 1.4; }
    .module-action { display: flex; flex-direction: column; gap: 12px; margin-top: 18px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
    .module-action .form-switch { min-height: 48px; display: flex; align-items: center; gap: 10px; margin: 0; padding-left: 3.2rem; }
    .module-action .form-check-input { width: 2.6rem; height: 1.45rem; margin-left: -3.2rem; }
    .module-action .btn { min-height: 48px; }
    .modules-empty { grid-column: 1 / -1; padding: 40px 20px; border: 1px dashed #cbd5e1; border-radius: 16px; text-align: center; background: #fff; }
    .modules-empty > i { color: var(--text-muted); font-size: 2rem; }
    .modules-empty h2 { margin: 10px 0 5px; font-size: 1.1rem; }
    .modules-empty p { margin: 0; color: var(--text-muted); }
    @media (min-width: 768px) {
        .modules-header { flex-direction: row; align-items: flex-start; justify-content: space-between; }
        .modules-back { align-self: auto; }
        .modules-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .module-action { flex-direction: row; align-items: center; justify-content: space-between; }
        .module-action .btn { min-width: 136px; }
    }
    @media (min-width: 1200px) { .modules-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
</style>
@endsection

@push('scripts')
<script>
    document.getElementById('module-errors')?.focus();
    document.querySelectorAll('.module-action').forEach((form) => {
        const toggle = form.querySelector('[role="switch"]');
        const button = form.querySelector('button[type="submit"]');
        toggle?.addEventListener('change', () => {
            if (!button) return;
            button.textContent = toggle.checked ? 'Habilitar' : 'Deshabilitar';
            button.classList.toggle('btn-primary', toggle.checked);
            button.classList.toggle('btn-outline-secondary', !toggle.checked);
        });
        form.addEventListener('submit', () => {
            if (!button) return;
            button.disabled = true;
            button.textContent = 'Guardando…';
        });
    });
</script>
@endpush
