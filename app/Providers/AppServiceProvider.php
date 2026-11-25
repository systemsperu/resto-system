<?php

namespace App\Providers;

use App\Models\InventoryLog;
use App\Models\Location;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\SitePage;
use App\Models\SiteSection;
use App\Modules\Contracts\ModuleStateStore;
use App\Modules\SettingModuleStateStore;
use App\Observers\SyncableObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModuleStateStore::class, SettingModuleStateStore::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Order::class, Payment::class, InventoryLog::class, Reservation::class, Product::class, Location::class, SitePage::class, SiteSection::class] as $model) {
            $model::observe(SyncableObserver::class);
        }

        // Compartir el símbolo de moneda con TODAS las vistas
        // Usamos un try-catch para evitar errores si la tabla settings aún no existe (durante migraciones)
        try {
            if (Schema::hasTable('settings')) {
                $currency = Setting::where('key', 'currency_symbol')->value('value') ?? '$';

                // Ahora la variable $currency estará disponible en cualquier archivo .blade.php
                View::share('currency', $currency);
            }
        } catch (\Exception $e) {
            // Si falla (ej: base de datos caída), usamos default
            View::share('currency', '$');
        }
    }
}
