<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\InvoiceItem;
use App\Observers\InvoiceItemObserver;
use App\Models\PurchaseOrderItem;
use App\Observers\PurchaseOrderItemObserver;
use App\Observers\AdjustmentObserver;
use App\Observers\InventoryObserver;
use App\Models\Adjustment;
use App\Models\Product;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       /*  $this->app->singleton(StockManager::class, function ($app) {
            return new StockManager();
        }); */
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        InvoiceItem::observe(InvoiceItemObserver::class);
        PurchaseOrderItem::observe(PurchaseOrderItemObserver::class);
        Adjustment::observe(AdjustmentObserver::class);
        Product::observe(InventoryObserver::class);
    }
}
