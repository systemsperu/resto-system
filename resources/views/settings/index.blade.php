@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0"><i class="bi bi-gear-fill me-2"></i>Configuración</h2>
                    <p class="text-muted mb-0">Personaliza la identidad y región de tu negocio</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger" role="alert" tabindex="-1" id="settings-errors">
                            <h2 class="h6 fw-bold">Revisa los datos antes de guardar</h2>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <h5 class="fw-bold text-primary mb-3">Datos de la Empresa</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label fw-bold">Nombre comercial</label>
                                <input id="company_name" type="text" name="company_name" class="form-control" value="{{ old('company_name', $settings['company_name'] ?? '') }}" placeholder="Ej: Recreo El Águila" required autocomplete="organization">
                            </div>
                            <div class="col-md-6">
                                <label for="company_phone" class="form-label fw-bold">Teléfono / pedidos</label>
                                <input id="company_phone" type="tel" name="company_phone" class="form-control" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}" placeholder="Ej: 999 888 777" autocomplete="tel">
                            </div>
                            <div class="col-md-6">
                                <label for="company_email" class="form-label fw-bold">Correo público</label>
                                <input id="company_email" type="email" name="company_email" class="form-control" value="{{ old('company_email', $settings['company_email'] ?? '') }}" placeholder="contacto@empresa.pe" autocomplete="email">
                            </div>
                            <div class="col-md-6">
                                <label for="company_website" class="form-label fw-bold">Sitio web</label>
                                <input id="company_website" type="url" name="company_website" class="form-control" value="{{ old('company_website', $settings['company_website'] ?? '') }}" placeholder="https://empresa.pe" autocomplete="url">
                            </div>
                            <div class="col-12">
                                <label for="company_address" class="form-label fw-bold">Dirección</label>
                                <input id="company_address" type="text" name="company_address" class="form-control" value="{{ old('company_address', $settings['company_address'] ?? '') }}" placeholder="Ej: Av. Principal 123" autocomplete="street-address">
                            </div>
                            <div class="col-6 col-md-2">
                                <label for="company_country_code" class="form-label fw-bold">País</label>
                                <input id="company_country_code" type="text" name="company_country_code" class="form-control text-uppercase" value="{{ old('company_country_code', $settings['company_country_code'] ?? 'PE') }}" maxlength="2" placeholder="PE">
                            </div>
                            <div class="col-6 col-md-3">
                                <label for="company_department" class="form-label fw-bold">Departamento</label>
                                <input id="company_department" type="text" name="company_department" class="form-control" value="{{ old('company_department', $settings['company_department'] ?? '') }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="company_province" class="form-label fw-bold">Provincia</label>
                                <input id="company_province" type="text" name="company_province" class="form-control" value="{{ old('company_province', $settings['company_province'] ?? '') }}">
                            </div>
                            <div class="col-7 col-md-2">
                                <label for="company_district" class="form-label fw-bold">Distrito</label>
                                <input id="company_district" type="text" name="company_district" class="form-control" value="{{ old('company_district', $settings['company_district'] ?? '') }}">
                            </div>
                            <div class="col-5 col-md-2">
                                <label for="company_ubigeo" class="form-label fw-bold">Ubigeo</label>
                                <input id="company_ubigeo" type="text" inputmode="numeric" name="company_ubigeo" class="form-control" value="{{ old('company_ubigeo', $settings['company_ubigeo'] ?? '') }}" maxlength="6" pattern="[0-9]{6}">
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        <h5 class="fw-bold text-primary mb-3">Región y Sistema</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><i class="bi bi-clock"></i> Zona Horaria</label>
                                <select name="timezone" class="form-select bg-light border-primary">
                                    @foreach($timezones as $tz => $label)
                                        <option value="{{ $tz }}" {{ ($settings['timezone'] ?? 'America/Lima') == $tz ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hora actual del sistema: <strong>{{ \Carbon\Carbon::now()->format('H:i:s') }}</strong></small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold">Moneda</label>
                                <select name="currency_symbol" class="form-select">
                                    <option value="S/" {{ ($settings['currency_symbol'] ?? '') == 'S/' ? 'selected' : '' }}>S/ (Soles)</option>
                                    <option value="$" {{ ($settings['currency_symbol'] ?? '') == '$' ? 'selected' : '' }}>$ (Dólares)</option>
                                    <option value="€" {{ ($settings['currency_symbol'] ?? '') == '€' ? 'selected' : '' }}>€ (Euros)</option>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Mensaje Pie de Ticket</label>
                                <input type="text" name="ticket_footer" class="form-control" value="{{ $settings['ticket_footer'] ?? '¡Gracias por su visita!' }}">
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        @php
                            $brandPrimary = old('brand_primary_color', $settings['brand_primary_color'] ?? '#1F2937');
                            $brandSecondary = old('brand_secondary_color', $settings['brand_secondary_color'] ?? '#F59E0B');
                            $brandIcon = $settings['brand_icon'] ?? ($settings['company_logo'] ?? null);
                            $brandFavicon = $settings['favicon'] ?? $brandIcon;
                        @endphp
                        <h5 class="fw-bold text-primary mb-1">Identidad visual</h5>
                        <p class="text-muted small mb-3">Define la marca que verán el personal y tus clientes.</p>

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-xl-7">
                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <label for="brand_primary_color" class="form-label fw-bold">Color primario</label>
                                        <div class="input-group">
                                            <input id="brand_primary_picker" type="color" class="form-control form-control-color flex-shrink-0" value="{{ $brandPrimary }}" aria-label="Seleccionar color primario">
                                            <input id="brand_primary_color" type="text" name="brand_primary_color" class="form-control text-uppercase" value="{{ $brandPrimary }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" inputmode="text" required>
                                        </div>
                                        @error('brand_primary_color')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label for="brand_secondary_color" class="form-label fw-bold">Color secundario</label>
                                        <div class="input-group">
                                            <input id="brand_secondary_picker" type="color" class="form-control form-control-color flex-shrink-0" value="{{ $brandSecondary }}" aria-label="Seleccionar color secundario">
                                            <input id="brand_secondary_color" type="text" name="brand_secondary_color" class="form-control text-uppercase" value="{{ $brandSecondary }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" inputmode="text" required>
                                        </div>
                                        @error('brand_secondary_color')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="company_logo" class="form-label fw-bold">Logo principal</label>
                                        <input id="company_logo" type="file" name="company_logo" class="form-control" accept="image/jpeg,image/png,image/webp">
                                        <div class="form-text">Horizontal o apaisado, JPG, PNG o WebP. Máximo 4 MB.</div>
                                        @if(!empty($settings['company_logo']))
                                            <div class="form-check mt-2">
                                                <input id="remove_company_logo" class="form-check-input" type="checkbox" name="remove_company_logo" value="1">
                                                <label class="form-check-label" for="remove_company_logo">Retirar logo actual</label>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="brand_icon" class="form-label fw-bold">Icono de la aplicación</label>
                                        <input id="brand_icon" type="file" name="brand_icon" class="form-control" accept="image/png,image/webp">
                                        <div class="form-text">Imagen cuadrada PNG o WebP, máximo 1 MB.</div>
                                        @if(!empty($settings['brand_icon']))
                                            <div class="form-check mt-2">
                                                <input id="remove_brand_icon" class="form-check-input" type="checkbox" name="remove_brand_icon" value="1">
                                                <label class="form-check-label" for="remove_brand_icon">Retirar icono actual</label>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label for="favicon" class="form-label fw-bold">Favicon</label>
                                        <input id="favicon" type="file" name="favicon" class="form-control" accept="image/png,image/x-icon,.ico">
                                        <div class="form-text">PNG o ICO, máximo 512 KB.</div>
                                        @if(!empty($settings['favicon']))
                                            <div class="form-check mt-2">
                                                <input id="remove_favicon" class="form-check-input" type="checkbox" name="remove_favicon" value="1">
                                                <label class="form-check-label" for="remove_favicon">Retirar favicon actual</label>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div id="brand-preview" class="rounded-4 border overflow-hidden shadow-sm" style="--preview-primary: {{ $brandPrimary }}; --preview-secondary: {{ $brandSecondary }};">
                                    <div class="p-3 d-flex align-items-center gap-3 text-white" style="background:var(--preview-primary); min-height:84px;">
                                        @if($brandIcon)
                                            <img id="brand-preview-icon" src="{{ asset('storage/'.$brandIcon) }}" width="52" height="52" class="rounded-3 bg-white" style="object-fit:cover;" alt="Vista previa del icono">
                                        @else
                                            <div id="brand-preview-icon-placeholder" class="rounded-3 bg-white d-flex align-items-center justify-content-center" style="width:52px;height:52px;color:var(--preview-primary);"><i class="bi bi-shop fs-4"></i></div>
                                        @endif
                                        <div class="min-w-0">
                                            <div id="brand-preview-name" class="fw-bold text-truncate">{{ old('company_name', $settings['company_name'] ?? config('app.name')) }}</div>
                                            <small class="opacity-75">Vista previa móvil</small>
                                        </div>
                                    </div>
                                    <div class="bg-white p-3">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            @if(!empty($settings['company_logo']))
                                                <img id="brand-preview-logo" src="{{ asset('storage/'.$settings['company_logo']) }}" class="img-fluid" style="max-height:56px;max-width:180px;object-fit:contain;" alt="Vista previa del logo">
                                            @else
                                                <strong id="brand-preview-logo-placeholder">Logo principal</strong>
                                            @endif
                                        </div>
                                        <button type="button" class="btn w-100 fw-bold" style="min-height:48px;background:var(--preview-secondary);color:#111827;">Acción destacada</button>
                                        <div class="d-flex align-items-center gap-2 mt-3 small text-muted">
                                            @if($brandFavicon)
                                                <img id="brand-preview-favicon" src="{{ asset('storage/'.$brandFavicon) }}" width="24" height="24" style="object-fit:contain;" alt="Vista previa del favicon">
                                            @else
                                                <span id="brand-preview-favicon-placeholder" class="d-inline-flex align-items-center justify-content-center border rounded" style="width:24px;height:24px;"><i class="bi bi-globe2"></i></span>
                                            @endif
                                            <span>Icono del navegador</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">

                        {{-- ═══════════════════════════════════════════════════════════════ --}}
                        {{--    FACTURACIÓN ELECTRÓNICA — SUNAT (PERÚ)                       --}}
                        {{-- ═══════════════════════════════════════════════════════════════ --}}
                        <h5 class="fw-bold text-danger mb-3">
                            <i class="bi bi-receipt-cutoff me-2"></i>Facturación Electrónica · SUNAT
                        </h5>

                        @php
                            $ambiente   = $settings['sunat_environment'] ?? 'beta';
                            $tieneCert  = !empty($settings['sunat_cert_path']);
                        @endphp

                        <div class="alert {{ $ambiente === 'produccion' ? 'alert-danger' : 'alert-warning' }} py-2 mb-3">
                            <strong>Ambiente actual:</strong>
                            {{ $ambiente === 'produccion' ? 'PRODUCCIÓN (emisión real)' : 'BETA (pruebas)' }}
                            @if(!$tieneCert)
                                · <span class="badge bg-secondary">Usando certificado demo</span>
                            @endif
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">RUC del emisor <span class="text-danger">*</span></label>
                                <input type="text" name="sunat_ruc" class="form-control"
                                       value="{{ $settings['sunat_ruc'] ?? '' }}"
                                       maxlength="11" pattern="\d{11}" placeholder="20000000001">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold">Razón social <span class="text-danger">*</span></label>
                                <input type="text" name="sunat_razon_social" class="form-control"
                                       value="{{ $settings['sunat_razon_social'] ?? '' }}"
                                       placeholder="MI EMPRESA SAC">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Nombre comercial</label>
                                <input type="text" name="sunat_nombre_comercial" class="form-control"
                                       value="{{ $settings['sunat_nombre_comercial'] ?? '' }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Dirección fiscal</label>
                                <input type="text" name="sunat_direccion_fiscal" class="form-control"
                                       value="{{ $settings['sunat_direccion_fiscal'] ?? '' }}"
                                       placeholder="AV. PRINCIPAL 123">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-bold">Ubigeo</label>
                                <input type="text" name="sunat_ubigeo" class="form-control"
                                       value="{{ $settings['sunat_ubigeo'] ?? '150101' }}"
                                       maxlength="6" placeholder="150101">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Departamento</label>
                                <input type="text" name="sunat_departamento" class="form-control"
                                       value="{{ $settings['sunat_departamento'] ?? 'LIMA' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Provincia</label>
                                <input type="text" name="sunat_provincia" class="form-control"
                                       value="{{ $settings['sunat_provincia'] ?? 'LIMA' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Distrito</label>
                                <input type="text" name="sunat_distrito" class="form-control"
                                       value="{{ $settings['sunat_distrito'] ?? 'LIMA' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">Urbanización</label>
                                <input type="text" name="sunat_urbanizacion" class="form-control"
                                       value="{{ $settings['sunat_urbanizacion'] ?? '-' }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Ambiente <span class="text-danger">*</span></label>
                                <select name="sunat_environment" class="form-select fw-bold">
                                    <option value="beta" {{ $ambiente === 'beta' ? 'selected' : '' }}>
                                        BETA (Pruebas)
                                    </option>
                                    <option value="produccion" {{ $ambiente === 'produccion' ? 'selected' : '' }} {{ !$sunatSendEnabled ? 'disabled' : '' }}>
                                        PRODUCCIÓN (Real)
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">IGV (%)</label>
                                <input type="number" step="0.01" name="sunat_igv_rate" class="form-control"
                                       value="{{ $settings['sunat_igv_rate'] ?? '18' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Usuario SOL</label>
                                <input type="text" name="sunat_sol_user" class="form-control"
                                       value="" autocomplete="off"
                                       placeholder="{{ $secretStatus['sunat_sol_user'] ? 'Configurado · dejar vacío para conservar' : 'MODDATOS para BETA' }}">
                                <small class="text-muted">La credencial guardada nunca se muestra nuevamente.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Clave SOL</label>
                                <input type="password" name="sunat_sol_pass" class="form-control"
                                       value="" autocomplete="new-password"
                                       placeholder="{{ $secretStatus['sunat_sol_pass'] ? 'Configurada · dejar vacío para conservar' : 'Sin configurar' }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-shield-lock"></i> Certificado digital (.pfx)
                                </label>
                                <input type="file" name="sunat_cert_file" class="form-control" accept=".pfx,.p12">
                                @if($tieneCert)
                                    <small class="text-success">
                                        <i class="bi bi-check-circle"></i>
                                        Certificado privado cargado
                                    </small>
                                @else
                                    <small class="text-warning">
                                        Sin certificado real. Para BETA usa el demo
                                        (<code>php artisan sunat:cert:demo</code>).
                                        Para producción sube tu <code>.pfx</code> oficial.
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Contraseña del .pfx</label>
                                <input type="password" name="sunat_cert_password" class="form-control"
                                       value="" autocomplete="new-password"
                                       placeholder="{{ $secretStatus['sunat_cert_password'] ? 'Configurada · dejar vacío para conservar' : 'Sin configurar' }}">
                            </div>
                        </div>

                        <div class="mt-5 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5 fw-bold shadow" style="min-height: 48px;">
                                <i class="bi bi-save me-2"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const preview = document.getElementById('brand-preview');
        if (!preview) return;

        const hexPattern = /^#[0-9A-F]{6}$/i;
        const connectColor = (textId, pickerId, property) => {
            const textInput = document.getElementById(textId);
            const picker = document.getElementById(pickerId);
            if (!textInput || !picker) return;

            picker.addEventListener('input', () => {
                textInput.value = picker.value.toUpperCase();
                preview.style.setProperty(property, picker.value);
            });
            textInput.addEventListener('input', () => {
                const value = textInput.value.trim();
                if (hexPattern.test(value)) {
                    picker.value = value;
                    preview.style.setProperty(property, value);
                }
            });
        };

        connectColor('brand_primary_color', 'brand_primary_picker', '--preview-primary');
        connectColor('brand_secondary_color', 'brand_secondary_picker', '--preview-secondary');

        document.getElementById('company_name')?.addEventListener('input', (event) => {
            document.getElementById('brand-preview-name').textContent = event.target.value || 'Nombre del negocio';
        });

        const previewFile = (inputId, imageId, placeholderId, alt, styles) => {
            document.getElementById(inputId)?.addEventListener('change', (event) => {
                const file = event.target.files?.[0];
                if (!file) return;

                let image = document.getElementById(imageId);
                if (!image) {
                    image = document.createElement('img');
                    image.id = imageId;
                    image.alt = alt;
                    image.className = 'img-fluid';
                    image.style.cssText = styles;
                    document.getElementById(placeholderId)?.replaceWith(image);
                }
                image.src = URL.createObjectURL(file);
            });
        };

        previewFile('company_logo', 'brand-preview-logo', 'brand-preview-logo-placeholder', 'Vista previa del logo', 'max-height:56px;max-width:180px;object-fit:contain;');
        previewFile('brand_icon', 'brand-preview-icon', 'brand-preview-icon-placeholder', 'Vista previa del icono', 'width:52px;height:52px;object-fit:cover;border-radius:.75rem;background:white;');
        previewFile('favicon', 'brand-preview-favicon', 'brand-preview-favicon-placeholder', 'Vista previa del favicon', 'width:24px;height:24px;object-fit:contain;');
    })();
</script>
@endpush
