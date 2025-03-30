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

            $stockBefore = $product->quantity_in_stock;
            $product->decrement('quantity_in_stock', $item->quantity);
            $stockAfter = $product->quantity_in_stock;

            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'quantity' => $item->quantity,
                'movement_type' => 'exit',
                'reference_type' => 'invoice',
                'reference_id' => $item->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => "Vente Invoice #{$item->invoice_id}"
            ]);

            $product->checkStockAlert();
        });
    }

    public function updated(InvoiceItem $item)
    {
        if ($item->isDirty('quantity')) {
            DB::transaction(function () use ($item) {
                $oldQuantity = $item->getOriginal('quantity');
                $newQuantity = $item->quantity;
                $diff = $oldQuantity - $newQuantity;

                $product = Product::lockForUpdate()->find($item->product_id);

                $stockBefore = $product->quantity_in_stock;

                if($diff > 0) {
                    $product->increment('quantity_in_stock', abs($diff));
                    $movementType = 'entry'; // On remet du stock
                } else {
                    $product->decrement('quantity_in_stock', abs($diff));
                    $movementType = 'exit'; // On retire du stock
                }

                $stockAfter = $product->quantity_in_stock;

                Inventory::create([
                    'date' => now(),
                    'product_id' => $product->id,
                    'quantity' => abs($diff),
                    'movement_type' => $movementType,
                    'reference_type' => 'invoice',
                    'reference_id' => $item->id,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
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

            $stockBefore = $product->quantity_in_stock;
            $product->increment('quantity_in_stock', $item->quantity);
            $stockAfter = $product->quantity_in_stock;

            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'quantity' => $item->quantity,
                'movement_type' => 'entry',
                'reference_type' => 'invoice',
                'reference_id' => $item->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => "Annulation Vente #{$item->invoice_id}"
            ]);

            $product->checkStockAlert();
        });
    }
}
