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

public function deleted(InvoiceItem $item)
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
