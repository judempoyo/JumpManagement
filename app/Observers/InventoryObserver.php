<?php

namespace App\Observers;

use App\Models\Inventory;
use Illuminate\Support\Facades\Cache;

class InventoryObserver
{
    public function saved(Inventory $inventory)
    {
        Cache::forget("product_{$inventory->product_id}_entries_sum");
        Cache::forget("product_{$inventory->product_id}_exits_sum");
        Cache::forget("product_{$inventory->product_id}_last_movement");
    }
    
    public function deleted($model)
{
    if ($model instanceof Inventory) {
        $this->clearProductCache($model->product_id);
    }
    // Ne rien faire si c'est un Product
}

protected function clearProductCache($productId)
{
    Cache::forget("product_{$productId}_entries_sum");
    Cache::forget("product_{$productId}_exits_sum");
    Cache::forget("product_{$productId}_last_movement");
}
}