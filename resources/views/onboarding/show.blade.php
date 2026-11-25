@php
    $brand = app(\App\Branding\BrandSettings::class)->get();
    $brandPrimary = preg_match('/^#[0-9A-F]{6}$/', $brand->primaryColor) ? $brand->primaryColor : '#1F2937';
    $brandSecondary = preg_match('/^#[0-9A-F]{6}$/', $brand->secondaryColor) ? $brand->secondaryColor : '#F59E0B';
    $step = max(1, min(5, (int) ($currentStep ?? 1)));
    $state = is_array($onboarding ?? null) ? $onboarding : [];
    $data = is_array(data_get($state, 'values')) ? data_get($state, 'values') : [];
    $completedSteps = array_map('intval', data_get($state, 'completed_steps', []));
    $field = fn (string $key, mixed $default = '') => old($key, data_get($data, $key, $default));
    $steps = [
        1 => 'Identidad',
        2 => 'Marca',
        3 => 'Contacto y ubicación',
        4 => 'Región y operación',
        5 => 'Revisión',
    ];
    $logoPath = data_get($data, 'company_logo');
    $iconPath = data_get($data, 'brand_icon') ?: $logoPath;
    $faviconPath = data_get($data, 'favicon') ?: $iconPath;
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Paso {{ $step }}: {{ $steps[$step] }} — Configura tu negocio</title>
    @if($faviconPath)
        <link rel="icon" href="{{ asset('storage/'.$faviconPath) }}">
    @endif
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
    <style>
        :root {
            --brand-primary: {{ $brandPrimary }};
            --brand-secondary: {{ $brandSecondary }};
            --surface-page: #f8fafc;
            --surface-card: #fff;
            --surface-muted: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --border-default: #cbd5e1;
            --focus-ring: #2563eb;
            --danger: #b91c1c;
            --radius: 1rem;
            --control-height: 48px;
        }
        * { box-sizing: border-box; }
        html { scroll-padding-bottom: 7.5rem; }
        body { margin: 0; min-width: 320px; color: var(--text-primary); background: var(--surface-page); font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif; }
        :focus-visible { outline: 3px solid var(--focus-ring); outline-offset: 3px; }
        .setup-shell { width: min(100%, 760px); margin: 0 auto; padding: 0 16px 132px; }
        .setup-header { padding: 20px 0 14px; }
        .setup-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .setup-brand__mark { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; flex: 0 0 auto; overflow: hidden; background: var(--brand-primary); color: #fff; font-weight: 800; }
        .setup-brand__mark img { width: 100%; height: 100%; object-fit: cover; background: #fff; }
        .setup-brand__title { margin: 0; font-size: 1rem; font-weight: 800; }
        .setup-brand__hint { margin: 2px 0 0; color: var(--text-secondary); font-size: .8125rem; }
        .setup-progress { margin-bottom: 18px; }
        .setup-progress__copy { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 8px; font-size: .875rem; }
        .setup-progress__copy strong { color: var(--brand-primary); }
        .setup-progress__track { width: 100%; height: 8px; border-radius: 99px; overflow: hidden; background: #e2e8f0; }
        .setup-progress__bar { display: block; height: 100%; width: {{ $step * 20 }}%; background: var(--brand-primary); }
        .setup-steps { display: flex; gap: 8px; padding: 10px 2px 2px; overflow-x: auto; scrollbar-width: thin; }
        .setup-step { min-width: 44px; min-height: 44px; padding: 0 12px; border: 1px solid var(--border-default); border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; color: var(--text-secondary); background: #fff; text-decoration: none; font-weight: 700; }
        .setup-step[aria-current="step"] { color: #fff; border-color: var(--brand-primary); background: var(--brand-primary); }
        .setup-card { border: 1px solid #e2e8f0; border-radius: var(--radius); background: var(--surface-card); box-shadow: 0 10px 30px rgba(15,23,42,.06); overflow: hidden; }
        .setup-card__body { padding: 22px 16px; }
        h1 { margin: 0; font-size: clamp(1.5rem, 7vw, 2rem); line-height: 1.15; letter-spacing: -.025em; }
        .setup-intro { margin: 8px 0 22px; color: var(--text-secondary); line-height: 1.55; }
        .setup-grid { display: grid; gap: 18px; }
        .field { min-width: 0; }
        .field label, .field legend { display: block; margin-bottom: 7px; font-weight: 700; }
        .field .optional { color: var(--text-secondary); font-size: .75rem; font-weight: 500; }
        .field-help { display: block; margin-top: 6px; color: var(--text-secondary); font-size: .8125rem; line-height: 1.4; }
        .form-control, .form-select { min-height: var(--control-height); border-color: var(--border-default); border-radius: .75rem; }
        .form-control:focus, .form-select:focus { border-color: var(--focus-ring); box-shadow: 0 0 0 .2rem rgba(37,99,235,.18); }
        textarea.form-control { min-height: 96px; }
        .color-control { display: grid; grid-template-columns: 56px minmax(0, 1fr); gap: 8px; }
        .color-control input[type="color"] { width: 56px; padding: 5px; }
        .upload-preview { min-height: 92px; margin-top: 10px; padding: 12px; border: 1px dashed var(--border-default); border-radius: .75rem; display: flex; align-items: center; justify-content: center; background: var(--surface-muted); }
        .upload-preview img { max-width: 100%; max-height: 84px; object-fit: contain; }
        .error-summary { margin-bottom: 20px; padding: 16px; border-left: 4px solid var(--danger); border-radius: .75rem; background: #fef2f2; color: #7f1d1d; }
        .error-summary h2 { margin: 0 0 8px; font-size: 1rem; }
        .error-summary ul { margin: 0; padding-left: 20px; }
        .error-summary a { color: inherit; font-weight: 700; }
        .field-error { display: block; margin-top: 6px; color: var(--danger); font-size: .8125rem; font-weight: 600; }
        .review-list { display: grid; gap: 14px; }
        .review-card { padding: 16px; border: 1px solid #e2e8f0; border-radius: .875rem; background: #fff; }
        .review-card__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
        .review-card h2 { margin: 0; font-size: 1rem; }
        .review-card dl { display: grid; grid-template-columns: minmax(90px, .8fr) minmax(0, 1.2fr); gap: 7px 12px; margin: 0; font-size: .875rem; }
        .review-card dt { color: var(--text-secondary); font-weight: 500; }
        .review-card dd { margin: 0; overflow-wrap: anywhere; font-weight: 650; }
        .swatch { width: 28px; height: 28px; display: inline-block; border: 1px solid rgba(15,23,42,.2); border-radius: 8px; vertical-align: middle; }
        .setup-actions { position: fixed; z-index: 20; right: 0; bottom: 0; left: 0; padding: 12px 16px calc(12px + env(safe-area-inset-bottom)); border-top: 1px solid #e2e8f0; background: rgba(255,255,255,.96); backdrop-filter: blur(12px); }
        .setup-actions__inner { width: min(100%, 760px); margin: 0 auto; display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 8px; }
        .setup-actions .btn { min-height: var(--control-height); border-radius: .75rem; font-weight: 750; }
        .btn-brand { color: #fff; background: var(--brand-primary); }
        .btn-brand:hover, .btn-brand:focus { color: #fff; background: color-mix(in srgb, var(--brand-primary) 86%, #000); }
        .save-exit { min-height: 44px; margin-top: 14px; color: var(--text-secondary); background: none; border: 0; text-decoration: underline; text-underline-offset: 3px; }
        .setup-status { min-height: 1.5rem; margin: 12px 0 0; color: var(--text-secondary); text-align: center; font-size: .8125rem; }
        @media (min-width: 768px) {
            .setup-shell { padding-right: 24px; padding-left: 24px; }
            .setup-header { padding-top: 28px; }
            .setup-card__body { padding: 32px; }
            .setup-grid--two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .span-2 { grid-column: 1 / -1; }
            .setup-actions { padding-right: 24px; padding-left: 24px; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
<div class="setup-shell">
    <header class="setup-header">
        <div class="setup-brand">
            <div class="setup-brand__mark" aria-hidden="true">
                @if($iconPath)
                    <img src="{{ asset('storage/'.$iconPath) }}" alt="">
                @else
                    {{ mb_strtoupper(mb_substr((string) $field('business_name', 'N'), 0, 1)) }}
                @endif
            </div>
            <div>
                <p class="setup-brand__title">Configura tu negocio</p>
                <p class="setup-brand__hint">Puedes guardar y continuar después.</p>
            </div>
        </div>
    </header>

    <nav class="setup-progress" aria-label="Progreso de configuración">
        <div class="setup-progress__copy">
            <strong>Paso {{ $step }} de 5</strong>
            <span>{{ $steps[$step] }}</span>
        </div>
        <div class="setup-progress__track" role="progressbar" aria-valuemin="1" aria-valuemax="5" aria-valuenow="{{ $step }}" aria-label="Paso {{ $step }} de 5">
            <span class="setup-progress__bar"></span>
        </div>
        <div class="setup-steps" aria-label="Pasos disponibles">
            @foreach($steps as $number => $label)
                @if($number === $step || in_array($number, $completedSteps, true))
                    <a class="setup-step" href="{{ route('onboarding.show', ['step' => $number]) }}" @if($number === $step) aria-current="step" @endif aria-label="Paso {{ $number }}: {{ $label }}">{{ $number }}</a>
                @else
                    <span class="setup-step" aria-disabled="true" aria-label="Paso {{ $number }} pendiente">{{ $number }}</span>
                @endif
            @endforeach
        </div>
    </nav>

    <main>
        <section class="setup-card" aria-labelledby="step-title">
            <div class="setup-card__body">
                @if($errors->any())
                    <div class="error-summary" id="error-summary" role="alert" tabindex="-1">
                        <h2>Revisa los datos para continuar</h2>
                        <ul>
                            @foreach($errors->messages() as $name => $messages)
                                @foreach($messages as $message)
                                    <li><a href="#{{ $name }}">{{ $message }}</a></li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="onboarding-form" method="POST" action="{{ route('onboarding.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" name="step" value="{{ $step }}">

                    @if($step === 1)
                        <h1 id="step-title" tabindex="-1">Cuéntanos sobre tu negocio</h1>
                        <p class="setup-intro">Estos datos crearán la identidad base de esta instalación.</p>
                        <div class="setup-grid setup-grid--two">
                            <div class="field span-2">
                                <label for="business_name">Nombre comercial</label>
                                <input id="business_name" name="business_name" class="form-control" value="{{ $field('business_name') }}" minlength="2" maxlength="120" autocomplete="organization" required @error('business_name') aria-invalid="true" aria-describedby="business_name-error" @enderror>
                                @error('business_name')<span class="field-error" id="business_name-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label for="legal_name">Razón social <span class="optional">(opcional)</span></label>
                                <input id="legal_name" name="legal_name" class="form-control" value="{{ $field('legal_name') }}" maxlength="200" autocomplete="organization">
                            </div>
                            <div class="field">
                                <label for="tax_id">RUC <span class="optional">(opcional)</span></label>
                                <input id="tax_id" name="tax_id" class="form-control" value="{{ $field('tax_id') }}" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" aria-describedby="tax_id-help">
                                <small class="field-help" id="tax_id-help">No activa SUNAT ni realiza emisiones.</small>
                            </div>
                            <div class="field span-2">
                                <label for="business_tagline">Descripción breve <span class="optional">(opcional)</span></label>
                                <input id="business_tagline" name="business_tagline" class="form-control" value="{{ $field('business_tagline') }}" maxlength="120" placeholder="Ej.: Sabores de la selva y descanso en familia">
                            </div>
                            <div class="field span-2">
                                <label for="business_type">Tipo de negocio</label>
                                <select id="business_type" name="business_type" class="form-select" required>
                                    <option value="">Selecciona una opción</option>
                                    @foreach(['restaurant' => 'Restaurante', 'tourist_recreation' => 'Recreo turístico', 'restaurant_lodging' => 'Restaurante y alojamiento', 'lodging' => 'Alojamiento', 'other' => 'Otro'] as $value => $label)
                                        <option value="{{ $value }}" @selected($field('business_type') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @elseif($step === 2)
                        <h1 id="step-title" tabindex="-1">Dale identidad visual</h1>
                        <p class="setup-intro">Puedes reemplazar estos recursos después. El sistema conservará un monograma si no cargas logo.</p>
                        <div class="setup-grid setup-grid--two">
                            <div class="field span-2">
                                <label for="company_logo">Logo principal <span class="optional">(opcional)</span></label>
                                <input id="company_logo" name="company_logo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" aria-describedby="company_logo-help">
                                <small class="field-help" id="company_logo-help">PNG, JPG o WebP. Máximo 2 MB.</small>
                                <div class="upload-preview" id="company_logo-preview" aria-hidden="true">
                                    @if($logoPath)<img src="{{ asset('storage/'.$logoPath) }}" alt="">@else<span>Vista previa del logo</span>@endif
                                </div>
                                @if($logoPath)<label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_company_logo" value="1"> <span class="form-check-label">Retirar logo actual</span></label>@endif
                            </div>
                            <div class="field">
                                <label for="brand_icon">Isotipo <span class="optional">(opcional)</span></label>
                                <input id="brand_icon" name="brand_icon" type="file" class="form-control" accept="image/png,image/webp">
                                @if(data_get($data, 'brand_icon'))<label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_brand_icon" value="1"> <span class="form-check-label">Retirar isotipo</span></label>@endif
                            </div>
                            <div class="field">
                                <label for="favicon">Favicon <span class="optional">(opcional)</span></label>
                                <input id="favicon" name="favicon" type="file" class="form-control" accept="image/png,image/x-icon,.ico">
                                @if(data_get($data, 'favicon'))<label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_favicon" value="1"> <span class="form-check-label">Retirar favicon</span></label>@endif
                            </div>
                            <div class="field">
                                <label for="brand_primary_color">Color primario</label>
                                <div class="color-control">
                                    <input id="brand_primary_picker" type="color" class="form-control" value="{{ $field('brand_primary_color', '#1F2937') }}" aria-label="Seleccionar color primario">
                                    <input id="brand_primary_color" name="brand_primary_color" class="form-control text-uppercase" value="{{ $field('brand_primary_color', '#1F2937') }}" pattern="#[0-9A-Fa-f]{6}" maxlength="7" required>
                                </div>
                            </div>
                            <div class="field">
                                <label for="brand_secondary_color">Color secundario</label>
                                <div class="color-control">
                                    <input id="brand_secondary_picker" type="color" class="form-control" value="{{ $field('brand_secondary_color', '#F59E0B') }}" aria-label="Seleccionar color secundario">
                                    <input id="brand_secondary_color" name="brand_secondary_color" class="form-control text-uppercase" value="{{ $field('brand_secondary_color', '#F59E0B') }}" pattern="#[0-9A-Fa-f]{6}" maxlength="7" required>
                                </div>
                            </div>
                        </div>
                    @elseif($step === 3)
                        <h1 id="step-title" tabindex="-1">Contacto y ubicación</h1>
                        <p class="setup-intro">Esta información será pública en la carta y, más adelante, en la landing.</p>
                        <div class="setup-grid setup-grid--two">
                            @foreach([['contact_phone','Teléfono','tel'],['contact_whatsapp','WhatsApp','tel'],['contact_email','Correo público','email']] as [$name,$label,$type])
                                <div class="field"><label for="{{ $name }}">{{ $label }} <span class="optional">(opcional)</span></label><input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" class="form-control" value="{{ $field($name) }}"></div>
                            @endforeach
                            <div class="field span-2"><label for="address_line">Dirección</label><input id="address_line" name="address_line" class="form-control" value="{{ $field('address_line') }}" maxlength="250" autocomplete="street-address" required></div>
                            <div class="field"><label for="address_department">Departamento</label><input id="address_department" name="address_department" class="form-control" value="{{ $field('address_department') }}" required></div>
                            <div class="field"><label for="address_province">Provincia</label><input id="address_province" name="address_province" class="form-control" value="{{ $field('address_province') }}" required></div>
                            <div class="field"><label for="address_district">Distrito</label><input id="address_district" name="address_district" class="form-control" value="{{ $field('address_district') }}" required></div>
                            <div class="field"><label for="address_ubigeo">Ubigeo <span class="optional">(opcional)</span></label><input id="address_ubigeo" name="address_ubigeo" class="form-control" value="{{ $field('address_ubigeo') }}" inputmode="numeric" maxlength="6" pattern="[0-9]{6}"></div>
                            <div class="field span-2"><label for="address_reference">Referencia <span class="optional">(opcional)</span></label><input id="address_reference" name="address_reference" class="form-control" value="{{ $field('address_reference') }}" maxlength="180"></div>
                            <div class="field"><label for="latitude">Latitud <span class="optional">(opcional)</span></label><input id="latitude" name="latitude" class="form-control" value="{{ $field('latitude') }}" inputmode="decimal"></div>
                            <div class="field"><label for="longitude">Longitud <span class="optional">(opcional)</span></label><input id="longitude" name="longitude" class="form-control" value="{{ $field('longitude') }}" inputmode="decimal"></div>
                        </div>
                    @elseif($step === 4)
                        <h1 id="step-title" tabindex="-1">Región y operación</h1>
                        <p class="setup-intro">Revisa los formatos que usarán ventas, reportes y documentos.</p>
                        <div class="setup-grid setup-grid--two">
                            <div class="field"><label for="country_code">País</label><select id="country_code" name="country_code" class="form-select" required><option value="PE" @selected($field('country_code','PE') === 'PE')>Perú</option></select></div>
                            <div class="field"><label for="locale">Idioma</label><select id="locale" name="locale" class="form-select" required><option value="es-PE" @selected($field('locale','es-PE') === 'es-PE')>Español (Perú)</option></select></div>
                            <div class="field"><label for="timezone">Zona horaria</label><select id="timezone" name="timezone" class="form-select" required><option value="America/Lima" @selected($field('timezone','America/Lima') === 'America/Lima')>Lima (UTC-05:00)</option></select></div>
                            <div class="field"><label for="currency_code">Moneda</label><select id="currency_code" name="currency_code" class="form-select" required><option value="PEN" @selected($field('currency_code','PEN') === 'PEN')>Sol peruano (PEN)</option><option value="USD" @selected($field('currency_code') === 'USD')>Dólar estadounidense (USD)</option></select></div>
                            <div class="field"><label for="date_format">Formato de fecha</label><select id="date_format" name="date_format" class="form-select" required><option value="dd/MM/yyyy" @selected($field('date_format','dd/MM/yyyy') === 'dd/MM/yyyy')>31/12/2026</option><option value="yyyy-MM-dd" @selected($field('date_format') === 'yyyy-MM-dd')>2026-12-31</option></select></div>
                            <div class="field"><label for="week_starts_on">Inicio de semana</label><select id="week_starts_on" name="week_starts_on" class="form-select" required><option value="1" @selected((string)$field('week_starts_on','1') === '1')>Lunes</option><option value="0" @selected((string)$field('week_starts_on') === '0')>Domingo</option></select></div>
                            <div class="field span-2"><label for="ticket_footer">Mensaje del ticket <span class="optional">(opcional)</span></label><textarea id="ticket_footer" name="ticket_footer" class="form-control" maxlength="280">{{ $field('ticket_footer', '¡Gracias por su visita!') }}</textarea></div>
                            <div class="review-card span-2" aria-label="Vista previa de formatos"><dl><dt>Fecha y hora</dt><dd>{{ now()->format('d/m/Y H:i') }}</dd><dt>Monto</dt><dd>S/ 1,234.50</dd></dl></div>
                        </div>
                    @else
                        <h1 id="step-title" tabindex="-1">Revisa y aplica la identidad</h1>
                        <p class="setup-intro">Puedes volver a cualquier sección antes de terminar. SUNAT se configurará por separado.</p>
                        <div class="review-list">
                            @foreach([
                                1 => ['Identidad', ['Nombre' => $field('business_name','Pendiente'), 'Tipo' => $field('business_type','Pendiente'), 'RUC' => $field('tax_id','No informado')]],
                                2 => ['Marca', ['Color primario' => $field('brand_primary_color','#1F2937'), 'Color secundario' => $field('brand_secondary_color','#F59E0B'), 'Logo' => $logoPath ? 'Cargado' : 'Monograma temporal']],
                                3 => ['Contacto y ubicación', ['Teléfono' => $field('contact_phone','No informado'), 'Correo' => $field('contact_email','No informado'), 'Dirección' => $field('address_line','Pendiente')]],
                                4 => ['Región y operación', ['País' => $field('country_code','PE'), 'Zona horaria' => $field('timezone','America/Lima'), 'Moneda' => $field('currency_code','PEN')]],
                            ] as $number => [$title, $items])
                                <article class="review-card">
                                    <div class="review-card__head"><h2>{{ $title }}</h2><a href="{{ route('onboarding.show', ['step' => $number]) }}">Editar</a></div>
                                    <dl>@foreach($items as $label => $value)<dt>{{ $label }}</dt><dd>{{ $value }}</dd>@endforeach</dl>
                                </article>
                            @endforeach
                            <div class="field mt-2">
                                <div class="form-check">
                                    <input id="confirmed" name="confirmed" class="form-check-input" type="checkbox" value="1" required @checked(old('confirmed'))>
                                    <label class="form-check-label fw-bold" for="confirmed">Confirmo que estos datos identifican al negocio.</label>
                                </div>
                                @error('confirmed')<span class="field-error" id="confirmed-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    @endif
                </form>

                <form id="onboarding-exit-form" method="POST" action="{{ route('onboarding.exit') }}">
                    @csrf
                    <button class="save-exit w-100" type="submit">Guardar y salir</button>
                </form>
                <p class="setup-status" id="setup-status" aria-live="polite"></p>
            </div>
        </section>
    </main>
</div>

<footer class="setup-actions" aria-label="Acciones del asistente">
    <div class="setup-actions__inner">
        @if($step > 1)
            <a class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" href="{{ route('onboarding.show', ['step' => $step - 1]) }}">Atrás</a>
        @else
            <span></span>
        @endif
        <button id="continue-button" class="btn btn-brand" type="submit" form="onboarding-form">
            {{ $step === 5 ? 'Aplicar identidad' : 'Guardar y continuar' }}
        </button>
    </div>
</footer>

<script>
    (() => {
        const form = document.getElementById('onboarding-form');
        const status = document.getElementById('setup-status');
        const submit = document.getElementById('continue-button');
        let dirty = false;

        form?.addEventListener('input', () => { dirty = true; });
        form?.addEventListener('submit', () => {
            dirty = false;
            submit.disabled = true;
            submit.setAttribute('aria-disabled', 'true');
            status.textContent = '{{ $step === 5 ? 'Aplicando identidad…' : 'Guardando el paso…' }}';
        });
        document.getElementById('onboarding-exit-form')?.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', (event) => {
            if (!dirty) return;
            event.preventDefault();
            event.returnValue = '';
        });

        document.querySelectorAll('.error-summary a').forEach((link) => {
            link.addEventListener('click', () => document.querySelector(link.getAttribute('href'))?.focus());
        });
        document.getElementById('error-summary')?.focus();

        const connectColor = (textId, pickerId) => {
            const text = document.getElementById(textId);
            const picker = document.getElementById(pickerId);
            if (!text || !picker) return;
            picker.addEventListener('input', () => { text.value = picker.value.toUpperCase(); dirty = true; });
            text.addEventListener('input', () => { if (/^#[0-9A-F]{6}$/i.test(text.value)) picker.value = text.value; });
        };
        connectColor('brand_primary_color', 'brand_primary_picker');
        connectColor('brand_secondary_color', 'brand_secondary_picker');

        document.getElementById('company_logo')?.addEventListener('change', (event) => {
            const file = event.target.files?.[0];
            const preview = document.getElementById('company_logo-preview');
            if (!file || !preview) return;
            preview.replaceChildren(Object.assign(document.createElement('img'), { src: URL.createObjectURL(file), alt: '' }));
        });
    })();
</script>
</body>
</html>
