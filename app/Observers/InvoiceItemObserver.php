<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class InvoiceItemObserver
{
    public function created(InvoiceItem $item)
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
            'reference_type' => 'invoice',
            'reference_id' => $item->id,
            'notes' => "Vente Invoice #{$item->invoice_id}"
        ]);
        
        $product->checkStockAlert();
    });
}

public function updated(InvoiceItem $item)
{
    // Ne traiter que si la quantité a changé
    if ($item->isDirty('quantity')) {
        DB::transaction(function () use ($item) {
            $oldQuantity = $item->getOriginal('quantity');
            $newQuantity = $item->quantity;
            $diff = $oldQuantity - $newQuantity;
            
            $product = Product::lockForUpdate()->find($item->product_id);
            
            $initialStock = $product->quantity_in_stock;
            if($diff > 0) {
                $product->increment('quantity_in_stock', abs($diff)); // On remet la différence
            } else {
                $product->decrement('quantity_in_stock', abs($diff)); // On retire la différence
            }
            
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $initialStock,
                'final_stock' => $product->quantity_in_stock,
                'reference_type' => 'invoice',
                'reference_id' => $item->id,
                'notes' => "Modification Vente #{$item->invoice_id} (qty: $oldQuantity → $newQuantity)"
            ]);
            
            $product->checkStockAlert();
        });
    }
}

public function deleted(InvoiceItem $item)
{
    DB::transaction(function () use ($item) {
        $product = Product::lockForUpdate()->find($item->product_id);
        
       
        $initialStock = $product->quantity_in_stock;
        $product->increment('quantity_in_stock', $item->quantity);
        
        Inventory::create([
            'date' => now(),
            'product_id' => $product->id,
            'initial_stock' => $initialStock,
            'final_stock' => $product->quantity_in_stock,
            'reference_type' => 'invoice', // Correction du type
            'reference_id' => $item->id,
            'notes' => "Annulation Vente #{$item->invoice_id}"
        ]);
        
        $product->checkStockAlert();
    });
}
}
