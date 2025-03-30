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
    public function updated(PurchaseOrderItem $item)
{
    if ($item->isDirty('quantity')) {
        DB::transaction(function () use ($item) {
            $oldQuantity = $item->getOriginal('quantity');
            $newQuantity = $item->quantity;
            $diff = $newQuantity - $oldQuantity;
            
            $product = Product::lockForUpdate()->find($item->product_id);
            
            $initialStock = $product->quantity_in_stock;
            $product->increment('quantity_in_stock', $diff);
            
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $initialStock,
                'final_stock' => $product->quantity_in_stock,
                'reference_type' => 'purchase_order',
                'reference_id' => $item->id,
                'notes' => "Modification PO #{$item->purchase_order_id} (qty: $oldQuantity → $newQuantity)"
            ]);
            
            $product->checkStockAlert();
        });
    }
}

    /**
     * Handle the PurchaseOrderItem "deleted" event.
     *
     * @param  \App\Models\PurchaseOrderItem  $item
     * @return void
     */
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
