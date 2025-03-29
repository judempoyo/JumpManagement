<?php

namespace App\Observers;

use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;


class PurchaseOrderItemObserver
{
    public function created(PurchaseOrderItem $item)
    {
        //dd($item);
        // Check if the item is already received
        DB::transaction(function () use ($item) {
            $product = Product::lockForUpdate()->find($item->product_id);
            
            $initialStock = $product->quantity_in_stock;
            $product->increment('quantity_in_stock', $item->quantity);
            
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $initialStock,
                'final_stock' => $product->quantity_in_stock,
                'reference_type' => 'purchase_order',
                'reference_id' => $item->id,
                'notes' => "Réception PO #{$item->purchase_order_id}"
            ]);
            
            $product->checkStockAlert();
        });
    }
public function deleted(PurchaseOrderItem $item)
{
    DB::transaction(function () use ($item) {
        $product = Product::lockForUpdate()->find($item->product_id);
        
        $initialStock = $product->quantity_in_stock;
        $product->decrement('quantity_in_stock', $item->quantity);
        
        Inventory::create([
            'date' => now(),
            'product_id' => $product->id,
            'initial_stock' => $initialStock,
            'final_stock' => $product->quantity_in_stock,
            'reference_type' => 'purchase_order',
            'reference_id' => $item->id,
            'notes' => "Annulation PO #{$item->purchase_order_id}"
        ]);
    });
}
}
