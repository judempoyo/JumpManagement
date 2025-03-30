<?php

namespace App\Observers;

use App\Models\Inventory;
use Illuminate\Support\Facades\Cache;

class InventoryObserver
{
    public function saved(Inventory $inventory)
    {
        $this->clearProductCache($inventory->product_id);
    }

    public function deleted(Inventory $inventory)
    {
        $this->clearProductCache($inventory->product_id);
    }

    protected function clearProductCache($productId)
    {
        Cache::forget("product_{$productId}_entries_sum");
        Cache::forget("product_{$productId}_exits_sum");
        Cache::forget("product_{$productId}_last_movement");
    }
}