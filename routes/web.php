<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\BillingPdfController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\DailySummaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\LodgingController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuPdfController;
use App\Http\Controllers\ModifierGroupController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicTableOrderController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SiteAdminController;
use App\Http\Controllers\SiteAnalyticsController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SyncStatusController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (SISTEMA PROFESIONAL v5.0 - PRODUCCIÓN)
|--------------------------------------------------------------------------
*/

// --- 1. AUTENTICACIÓN Y PÚBLICO ---
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.perform');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/pwa/icon/{size}.png', [PwaController::class, 'icon'])->whereNumber('size')->name('pwa.icon');
Route::get('/offline', [PwaController::class, 'offline'])->name('pwa.offline');
Route::get('/sitio', [SiteController::class, 'home'])->middleware('module:landing')->name('site.home');
Route::get('/sitio/{page:slug}', [SiteController::class, 'show'])->middleware('module:landing')->name('site.page');
Route::get('/alojamiento/disponibilidad', [LodgingController::class, 'availability'])->middleware(['module:lodging', 'throttle:30,1'])->name('lodging.availability');

// Menú Digital Público (Escaneo QR)
Route::get('/menu', [MenuController::class, 'index'])
    ->middleware('module:restaurant')
    ->name('menu.index');
Route::get('/menu/pdf', [MenuPdfController::class, 'pdf'])->middleware('module:restaurant')->name('menu.pdf');
Route::get('/menu/qr.png', [MenuPdfController::class, 'qr'])->middleware('module:restaurant')->name('menu.qr');
Route::get('/carta/{location:slug}', [MenuController::class, 'location'])->middleware('module:restaurant')->name('menu.location');
Route::get('/mesa/{token}', [PublicTableOrderController::class, 'show'])->middleware('module:restaurant')->name('table-order.show');
Route::post('/mesa/{token}', [PublicTableOrderController::class, 'store'])->middleware(['module:restaurant', 'throttle:10,1'])->name('table-order.store');
Route::get('/solicitud/{publicId}', [PublicTableOrderController::class, 'status'])->middleware('module:restaurant')->name('table-order.status');

// --- 2. SISTEMA INTERNO ---
Route::middleware(['auth'])->group(function () {
    Route::get('/pwa/status', [PwaController::class, 'status'])->name('pwa.status');

    Route::middleware('module:lodging')->prefix('lodging')->name('lodging.')->group(function () {
        Route::get('/', [LodgingController::class, 'index'])->middleware('permission:lodging.view')->name('index');
        Route::get('/report', [LodgingController::class, 'report'])->middleware('role:admin')->name('report');
        Route::post('/reservations', [LodgingController::class, 'storeReservation'])->middleware('permission:lodging.reservations.manage')->name('reservations.store');
        Route::post('/reservations/{reservation}/transition', [LodgingController::class, 'transition'])->middleware('permission:lodging.checkin.manage')->name('reservations.transition');
        Route::post('/reservations/{reservation}/guests', [LodgingController::class, 'storeGuest'])->middleware('permission:lodging.guests.manage')->name('guests.store');
        Route::post('/reservations/{reservation}/payments', [LodgingController::class, 'storePayment'])->middleware('permission:lodging.payments.manage')->name('payments.store');
        Route::post('/housekeeping/{task}/complete', [LodgingController::class, 'completeCleaning'])->middleware('permission:lodging.cleaning.manage')->name('housekeeping.complete');
        Route::middleware('role:admin')->group(function () {
            Route::post('/types', [LodgingController::class, 'storeType'])->name('types.store');
            Route::post('/units', [LodgingController::class, 'storeUnit'])->name('units.store');
            Route::post('/rates', [LodgingController::class, 'storeRate'])->name('rates.store');
            Route::post('/units/{unit}/blocks', [LodgingController::class, 'storeBlock'])->name('blocks.store');
            Route::post('/blocks/{block}/release', [LodgingController::class, 'releaseBlock'])->name('blocks.release');
        });
    });

    Route::middleware(['permission:settings.manage'])->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
        Route::post('/onboarding/exit', [D::class, 'exit'])->name('onboarding.exit');
        Route::get('/settings/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::patch('/settings/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
    });

    // =========================================================
    // ZONA OPERATIVA (Accesible para Mozo, Cajero, Admin)
    // =========================================================

    // Rutas de Caja (Apertura y Cierre) - Todos pueden intentar entrar, pero el controller valida
    Route::middleware(['module:restaurant', 'permission:cash-register.manage'])->group(function () {
        Route::get('/cash-registers/open', [CashRegisterController::class, 'create'])->name('cash_registers.create');
        Route::post('/cash-registers/open', [CashRegisterController::class, 'store'])->name('cash_registers.store');
        Route::get('/cash-registers/close', [CashRegisterController::class, 'close'])->name('cash_registers.close');
        Route::post('/cash-registers/close', [CashRegisterController::class, 'processClose'])->name('cash_registers.processClose');
    });

    // POS (Punto de Venta) - Protegido para que cajeros abran caja primero
    Route::middleware(['module:restaurant', 'permission:pos.manage', 'cash_register'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/pos/table/{table}', [PosController::class, 'order'])->name('pos.order');
    });

    Route::middleware(['module:restaurant', 'permission:pos.manage'])->group(function () {
        Route::get('/table-requests', [PublicTableOrderController::class, 'pending'])->name('table-order.pending');
        Route::post('/table-requests/{orderRequest}/confirm', [PublicTableOrderController::class, 'confirm'])->name('table-order.confirm');
        Route::post('/table-requests/{orderRequest}/cancel', [PublicTableOrderController::class, 'cancel'])->name('table-order.cancel');
        Route::post('/pos/order/{table}/add', [PosController::class, 'addToOrder'])->name('pos.add');

        // --- NUEVA RUTA: CÓDIGO DE BARRAS ---
        Route::post('/pos/order/{table}/barcode', [PosController::class, 'addByBarcode'])->name('pos.barcode');

        // Herramientas de Orden
        Route::get('/pos/order/{order}/precheck', [PosController::class, 'precheck'])->name('pos.precheck');
        Route::get('/pos/order/{order}/kitchen-ticket', [PosController::class, 'kitchenTicket'])->name('pos.kitchen');
        Route::post('/pos/order/{order}/discount', [PosController::class, 'applyDiscount'])->middleware('permission:pos.discount')->name('pos.discount');
        Route::post('/pos/order/{order}/move', [PosController::class, 'moveTable'])->name('pos.move');

        // División de Cuenta
        Route::get('/pos/order/{order}/split-content', [PosController::class, 'getSplitContent'])->name('pos.split.content');
        Route::post('/pos/order/{order}/split', [PosController::class, 'processSplit'])->name('pos.split');

        // Gestión de Items
        Route::post('/pos/detail/{detail}/update', [PosController::class, 'updateQuantity'])->name('pos.update');
        Route::post('/pos/detail/{detail}/note', [PosController::class, 'updateNote'])->name('pos.note');
        Route::delete('/pos/detail/{detail}', [PosController::class, 'removeItem'])->name('pos.remove');
    });

    // Monitor de Cocina
    Route::get('/kitchen', [KitchenController::class, 'index'])->middleware(['module:restaurant', 'permission:kitchen.view'])->name('kitchen.index');
    Route::post('/kitchen/{detail}/status', [KitchenController::class, 'updateStatus'])->middleware(['module:restaurant', 'permission:kitchen.manage'])->name('kitchen.update');

    // RESERVAS Y AGENDA
    Route::middleware(['module:reservations', 'permission:reservations.manage'])->group(function () {
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::put('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])->name('reservations.status');
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->middleware('permission:reservations.delete')->name('reservations.destroy');
    });

    // =========================================================
    // ZONA FINANCIERA (Cajeros y Admins)
    // =========================================================
    Route::middleware(['role:admin,cashier'])->group(function () {
        // Cobro Final
        Route::post('/pos/order/{order}/checkout', [PosController::class, 'checkout'])->middleware(['module:restaurant', 'cash_register'])->name('pos.checkout');

        // Ventas, Caja y Gastos
        Route::get('/sales', [SaleController::class, 'index'])->middleware('module:restaurant')->name('sales.index');
        Route::get('/sales/daily-report', [SaleController::class, 'dailyReport'])->middleware('module:restaurant')->name('sales.daily.report');
        Route::get('/sales/{order}/ticket', [SaleController::class, 'ticket'])->middleware('module:restaurant')->name('sales.ticket');

        Route::resource('expenses', ExpenseController::class)->only(['store', 'destroy'])->middleware('module:restaurant');

        // ── DELIVERY ──────────────────────────────────────────
        Route::middleware(['module:delivery'])->group(function () {
            Route::get('/delivery', [DeliveryController::class, 'index'])->name('delivery.index');
            Route::get('/delivery/create', [DeliveryController::class, 'create'])->name('delivery.create');
            Route::post('/delivery', [DeliveryController::class, 'store'])->name('delivery.store');
            Route::get('/delivery/{delivery}', [DeliveryController::class, 'show'])->name('delivery.show');
            Route::post('/delivery/{delivery}/status', [DeliveryController::class, 'updateStatus'])->name('delivery.status');
            Route::post('/delivery/{delivery}/driver', [DeliveryController::class, 'assignDriver'])->name('delivery.driver');
            Route::post('/delivery/{delivery}/checkout', [DeliveryController::class, 'checkout'])->name('delivery.checkout');
            Route::post('/delivery/{delivery}/cancel', [DeliveryController::class, 'cancel'])->name('delivery.cancel');
            // Repartidores
            Route::middleware(['permission:delivery.drivers.manage'])->group(function () {
                Route::get('/delivery-drivers', [DeliveryController::class, 'driversIndex'])->name('delivery.drivers');
                Route::post('/delivery-drivers', [DeliveryController::class, 'driversStore'])->name('delivery.drivers.store');
                Route::put('/delivery-drivers/{driver}', [DeliveryController::class, 'driversUpdate'])->name('delivery.drivers.update');
                Route::delete('/delivery-drivers/{driver}', [DeliveryController::class, 'driversDestroy'])->name('delivery.drivers.destroy');
            });
        });
    });

    // =========================================================
    // ZONA ADMINISTRATIVA (Solo Admin)
    // =========================================================
    Route::middleware(['role:admin'])->group(function () {

        // ── FACTURACIÓN ELECTRÓNICA (SUNAT) ──────────────────
        Route::middleware(['module:sunat'])->group(function () {
            Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
            Route::get('/billing/{order}', [BillingController::class, 'show'])->name('billing.show');
            Route::post('/billing/{order}/retry', [BillingController::class, 'retry'])->name('billing.retry');
            Route::get('/billing/{order}/xml', [BillingController::class, 'downloadXml'])->name('billing.xml');
            Route::get('/billing/{order}/cdr', [BillingController::class, 'downloadCdr'])->name('billing.cdr');
            Route::get('/billing/{order}/pdf', [BillingPdfController::class, 'show'])->name('billing.pdf');
            Route::get('/billing/{order}/ticket', [BillingPdfController::class, 'ticket'])->name('billing.pdf.ticket');

            // Notas de Crédito
            Route::get('/credit-notes', [CreditNoteController::class, 'index'])->name('credit_notes.index');
            Route::get('/credit-notes/create/{order}', [CreditNoteController::class, 'create'])->name('credit_notes.create');
            Route::post('/credit-notes/{order}', [CreditNoteController::class, 'store'])->name('credit_notes.store');
            Route::get('/credit-notes/{creditNote}', [CreditNoteController::class, 'show'])->name('credit_notes.show');
            Route::post('/credit-notes/{creditNote}/retry', [CreditNoteController::class, 'retry'])->name('credit_notes.retry');
            Route::get('/credit-notes/{creditNote}/xml', [CreditNoteController::class, 'downloadXml'])->name('credit_notes.xml');
            Route::get('/credit-notes/{creditNote}/cdr', [CreditNoteController::class, 'downloadCdr'])->name('credit_notes.cdr');

            // Resumen Diario de Boletas
            Route::get('/daily-summaries', [DailySummaryController::class, 'index'])->name('daily_summaries.index');
            Route::post('/daily-summaries', [DailySummaryController::class, 'store'])->name('daily_summaries.store');
            Route::post('/daily-summaries/{summary}/check', [DailySummaryController::class, 'check'])->name('daily_summaries.check');
            Route::get('/daily-summaries/{summary}/xml', [DailySummaryController::class, 'downloadXml'])->name('daily_summaries.xml');
            Route::get('/daily-summaries/{summary}/cdr', [DailySummaryController::class, 'downloadCdr'])->name('daily_summaries.cdr');
        });

        // Dashboard y BI
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports', [ReportController::class, 'index'])->middleware('module:reports')->name('reports.index');

        // Gestión
        Route::get('/cash-registers', [CashRegisterController::class, 'index'])->middleware('module:restaurant')->name('cash_registers.index');
        Route::resource('clients', ClientController::class);
        Route::post('/clients/{client}/consent', [ClientController::class, 'consent'])->name('clients.consent');
        Route::middleware('module:marketing')->prefix('marketing')->name('marketing.')->group(function () {
            Route::get('/', [MarketingController::class, 'index'])->name('index');
            Route::post('/segments', [MarketingController::class, 'storeSegment'])->name('segments.store');
            Route::post('/campaigns', [MarketingController::class, 'storeCampaign'])->name('campaigns.store');
            Route::post('/campaigns/{campaign}/simulate', [MarketingController::class, 'simulateCampaign'])->name('campaigns.simulate');
            Route::post('/coupons', [MarketingController::class, 'storeCoupon'])->name('coupons.store');
            Route::post('/coupons/redeem', [MarketingController::class, 'redeemCoupon'])->name('coupons.redeem');
            Route::post('/loyalty/adjust', [MarketingController::class, 'adjustLoyalty'])->name('loyalty.adjust');
        });
        Route::resource('categories', CategoryController::class)->middleware('module:restaurant');

        // Productos e Inventario
        Route::post('/products/{product}/adjust', [ProductController::class, 'adjustStock'])->middleware('module:inventory')->name('products.adjust');
        Route::post('/products/{product}/toggle', [ProductController::class, 'toggleStatus'])->middleware('module:restaurant')->name('products.toggle');
        Route::resource('products', ProductController::class)->middleware('module:restaurant');
        Route::middleware('module:restaurant')->group(function () {
            Route::get('/modifiers', [ModifierGroupController::class, 'index'])->name('modifiers.index');
            Route::post('/modifiers', [ModifierGroupController::class, 'store'])->name('modifiers.store');
            Route::put('/modifiers/{group}', [ModifierGroupController::class, 'update'])->name('modifiers.update');
            Route::delete('/modifiers/{group}', [ModifierGroupController::class, 'destroy'])->name('modifiers.destroy');
            Route::post('/modifiers/{group}/options', [ModifierGroupController::class, 'storeOption'])->name('modifiers.options.store');
            Route::delete('/modifier-options/{option}', [ModifierGroupController::class, 'destroyOption'])->name('modifiers.options.destroy');
        });
        Route::get('/inventory/logs', function () {
            $logs = InventoryLog::with('product', 'user')->orderBy('created_at', 'desc')->paginate(50);

            return view('products.kardex', compact('logs'));
        })->middleware('module:inventory')->name('inventory.logs');

        // Configuración y Usuarios
        Route::resource('users', UserController::class);
        Route::middleware('permission:staff.manage')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::patch('/employees/{employee}/toggle', [EmployeeController::class, 'toggle'])->name('employees.toggle');
            Route::post('/employees/{employee}/shifts', [EmployeeController::class, 'storeShift'])->name('employees.shifts.store');
            Route::patch('/work-shifts/{shift}/cancel', [EmployeeController::class, 'cancelShift'])->name('work_shifts.cancel');
        });
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::middleware('module:landing')->prefix('site-admin')->name('site.admin.')->group(function () {
            Route::get('/', [SiteAdminController::class, 'index'])->name('index');
            Route::post('/pages', [SiteAdminController::class, 'storePage'])->name('pages.store');
            Route::put('/pages/{page}', [SiteAdminController::class, 'updatePage'])->name('pages.update');
            Route::post('/pages/{page}/sections', [SiteAdminController::class, 'storeSection'])->name('sections.store');
            Route::delete('/sections/{section}', [SiteAdminController::class, 'destroySection'])->name('sections.destroy');
            Route::get('/analytics', [SiteAnalyticsController::class, 'index'])->name('analytics');
            Route::post('/locations', [SiteAdminController::class, 'storeLocation'])->name('locations.store');
            Route::put('/locations/{location}/menu', [SiteAdminController::class, 'updateLocationMenu'])->name('locations.menu');
        });

        // MANTENIMIENTO DEL SISTEMA (RESET Y BACKUPS)
        Route::get('/system', [SystemController::class, 'index'])->name('system.index');
        Route::post('/system/reset', [SystemController::class, 'resetData'])->middleware('throttle:3,1')->name('system.reset');
        Route::post('/system/backup', [SystemController::class, 'backup'])->middleware('throttle:3,1')->name('system.backup');
        Route::post('/system/restore', [SystemController::class, 'restore'])->middleware('throttle:3,1')->name('system.restore');
        Route::get('/system/sync', [SyncStatusController::class, 'index'])->name('sync.index');
        Route::post('/system/sync/push', [SyncStatusController::class, 'push'])->middleware('throttle:3,1')->name('sync.push');
        Route::post('/system/sync/backup', [SyncStatusController::class, 'backup'])->middleware('throttle:2,1')->name('sync.backup');

        // Mapa de Mesas
        Route::get('/tables', [TableController::class, 'index'])->middleware('module:restaurant')->name('tables.index');
        Route::post('/tables/area', [TableController::class, 'storeArea'])->middleware('module:restaurant')->name('tables.storeArea');
        Route::put('/tables/area/{area}', [TableController::class, 'updateArea'])->middleware('module:restaurant')->name('tables.updateArea');
        Route::delete('/tables/area/{area}', [TableController::class, 'destroyArea'])->middleware('module:restaurant')->name('tables.destroyArea');
        Route::post('/tables/table', [TableController::class, 'storeTable'])->middleware('module:restaurant')->name('tables.storeTable');
        Route::put('/tables/table/{table}', [TableController::class, 'updateTable'])->middleware('module:restaurant')->name('tables.updateTable');
        Route::patch('/tables/table/{table}/waiter', [TableController::class, 'assignWaiter'])->middleware('module:restaurant')->name('tables.assignWaiter');
        Route::patch('/tables/table/{table}/toggle', [TableController::class, 'toggle'])->middleware('module:restaurant')->name('tables.toggle');
        Route::delete('/tables/table/{table}', [TableController::class, 'destroyTable'])->middleware('module:restaurant')->name('tables.destroyTable');
        Route::post('/tables/update-positions', [TableController::class, 'updatePositions'])->middleware('module:restaurant')->name('tables.updatePositions');
        Route::post('/tables/table/{table}/qr', [PublicTableOrderController::class, 'rotate'])->middleware('module:restaurant')->name('tables.qr.rotate');
        Route::get('/tables/table/{table}/qr.png', [PublicTableOrderController::class, 'qr'])->middleware('module:restaurant')->name('tables.qr.image');
    });

});
