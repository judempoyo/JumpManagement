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
                'reference_type' => 'purchase_order',
                'reference_id' => $item->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
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

                $stockBefore = $product->quantity_in_stock;
                $product->increment('quantity_in_stock', $diff);
                $stockAfter = $product->quantity_in_stock;

                $movementType = $diff > 0 ? 'entry' : 'exit';

                Inventory::create([
                    'date' => now(),
                    'product_id' => $product->id,
                    'quantity' => abs($diff),
                    'movement_type' => $movementType,
                    'reference_type' => 'purchase_order',
                    'reference_id' => $item->id,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'notes' => "Modification PO #{$item->purchase_order_id} (qty: $oldQuantity → $newQuantity)"
                ]);

                $product->checkStockAlert();
            });
        }
    }

    public function deleting(PurchaseOrderItem $item)
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
                'reference_type' => 'purchase_order',
                'reference_id' => $item->id,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => "Annulation PO #{$item->purchase_order_id}"
            ]);

            $product->checkStockAlert();
        });
    }
}
