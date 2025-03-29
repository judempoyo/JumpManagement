<?php

namespace App\Observers;

use App\Models\Adjustment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AdjustmentObserver
{
    public function created(Adjustment $adjustment)
    {
        DB::transaction(function () use ($adjustment) {
            $product = Product::lockForUpdate()->find($adjustment->product_id);
            
            $initialStock = $product->quantity_in_stock;
            
            if($adjustment->type === 'add') {
                $product->increment('quantity_in_stock', $adjustment->quantity);
            } else {
                $product->decrement('quantity_in_stock', $adjustment->quantity);
            }
            
            $adjustment->inventory()->create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $initialStock,
                'final_stock' => $product->quantity_in_stock,
                'reference_type' => 'adjustment',
                'reference_id' => $adjustment->id,
                'notes' => "Ajustement manuel: {$adjustment->reason}"
            ]);
            
            $product->checkStockAlert();
        });
    }
}