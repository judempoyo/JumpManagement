<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Inventory;

class PurchaseOrderObserver
{
    /**
     * Lorsqu'une commande est créée
     */
    public function created(PurchaseOrder $purchaseOrder)
    {
        // Augmenter les stocks pour chaque article
        foreach ($purchaseOrder->items as $item) {
            $product = $item->product;
            $product->quantity_in_stock += $item->quantity;
            $product->save();

            // Enregistrer dans l'historique des stocks
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $product->quantity_in_stock - $item->quantity,
                'final_stock' => $product->quantity_in_stock,
                'notes' => 'Réception de commande fournisseur #' . $purchaseOrder->id,
            ]);
        }
    }

    /**
     * Lorsqu'une commande est mise à jour
     */
    public function updated(PurchaseOrder $purchaseOrder)
    {
        // Si la commande est marquée comme payée
        if ($purchaseOrder->isDirty('paid') && $purchaseOrder->paid) {
            $purchaseOrder->createDebt();
        }
    }

    /**
     * Lorsqu'une commande est supprimée
     */
    public function deleted(PurchaseOrder $purchaseOrder)
    {
        // Diminuer les stocks si la commande est annulée
        foreach ($purchaseOrder->items as $item) {
            $product = $item->product;
            $product->quantity_in_stock = max(0, $product->quantity_in_stock - $item->quantity);
            $product->save();

            // Enregistrer dans l'historique des stocks
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $product->quantity_in_stock + $item->quantity,
                'final_stock' => $product->quantity_in_stock,
                'notes' => 'Annulation de commande fournisseur #' . $purchaseOrder->id,
            ]);
        }
    }
}