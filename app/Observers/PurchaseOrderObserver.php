<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\Log;

class PurchaseOrderObserver
{
  
    /**
     * Lorsqu'une commande est créée
     */
    public function created(PurchaseOrder $purchaseOrder)
    {
        //dd($purchaseOrder);

        // Chargez explicitement les items si la relation n'est pas déjà chargée
        if (!$purchaseOrder->relationLoaded('items')) {
            $purchaseOrder->load('items.product');
        }
    
        foreach ($purchaseOrder->items as $item) {
            // Chargez explicitement le produit si nécessaire
            if (!$item->relationLoaded('product')) {
                $item->load('product');
            }
    
            $product = $item->product;
            $initialStock = $product->quantity_in_stock;
            
            $product->quantity_in_stock += $item->quantity;
            $product->save(); // Assurez-vous que save() est bien appelé
    
            \Log::info("Stock mis à jour pour produit {$product->id}", [
                'ancien_stock' => $initialStock,
                'quantite_ajoutee' => $item->quantity,
                'nouveau_stock' => $product->quantity_in_stock
            ]);
    
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $initialStock,
                'final_stock' => $product->quantity_in_stock,
                'notes' => 'Réception PO #' . $purchaseOrder->id,
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