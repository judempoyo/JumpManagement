<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Invoice;
use App\Observers\InvoiceObserver;
use App\Models\PurchaseOrder;
use App\Observers\PurchaseOrderObserver;
use App\Services\StockManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StockManager::class, function ($app) {
            return new StockManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //Invoice::observe(InvoiceObserver::class);
        //PurchaseOrder::observe(PurchaseOrderObserver::class);
    }
}
