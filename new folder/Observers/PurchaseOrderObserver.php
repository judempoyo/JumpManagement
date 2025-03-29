<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "created" event.
     */
    public function created(PurchaseOrder $purchaseOrder)
    {
        // Utilisez une transaction globale pour toute l'opération
        DB::transaction(function () use ($purchaseOrder) {
            // Rechargez la commande avec ses relations fraîchement depuis la base
            $freshOrder = PurchaseOrder::with('items.product')->find($purchaseOrder->id);
            
            if ($freshOrder->items->isEmpty()) {
                Log::warning("Aucun article trouvé pour la commande #{$freshOrder->id} après rechargement", [
                    'order_id' => $freshOrder->id,
                    'items_in_db' => $freshOrder->items()->count()
                ]);
                return;
            }
    
            foreach ($freshOrder->items as $item) {
                if (!$item->product) {
                    Log::error("Produit manquant pour l'article #{$item->id}");
                    continue;
                }
    
                $product = $item->product;
                $initialStock = $product->quantity_in_stock;
                $newStock = $initialStock + $item->quantity;
    
                // Mise à jour du stock
                $product->quantity_in_stock = $newStock;
                $product->save();
    
                // Historique d'inventaire
                Inventory::create([
                    'date' => now(),
                    'product_id' => $product->id,
                    'initial_stock' => $initialStock,
                    'final_stock' => $newStock,
                    'notes' => 'Réception PO #' . $freshOrder->id,
                ]);
    
                Log::debug("Stock mis à jour - Produit: {$product->id}", [
                    'ancien' => $initialStock,
                    'ajout' => $item->quantity,
                    'nouveau' => $newStock
                ]);
            }
        });
    }

    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $purchaseOrder)
    {
        // Si la commande est marquée comme payée
        if ($purchaseOrder->isDirty('paid') && $purchaseOrder->paid) {
            $purchaseOrder->createDebt();
        }
    }

    /**
     * Handle the PurchaseOrder "deleted" event.
     */
    public function deleted(PurchaseOrder $purchaseOrder)
    {
        // Chargez les items avant suppression
        $purchaseOrder->load(['items.product']);

        foreach ($purchaseOrder->items as $item) {
            if (!$item->product) {
                Log::error("Impossible d'annuler le stock - article sans produit", [
                    'item_id' => $item->id,
                    'purchase_order_id' => $purchaseOrder->id
                ]);
                continue;
            }

            $product = $item->product;
            $newStock = max(0, $product->quantity_in_stock - $item->quantity);

            \DB::transaction(function () use ($product, $item, $newStock, $purchaseOrder) {
                $product->update(['quantity_in_stock' => $newStock]);

                Inventory::create([
                    'date' => now(),
                    'product_id' => $product->id,
                    'initial_stock' => $product->quantity_in_stock + $item->quantity,
                    'final_stock' => $newStock,
                    'notes' => 'Annulation de commande fournisseur #' . $purchaseOrder->id,
                ]);

                Log::info("Stock réduit après annulation de commande", [
                    'product_id' => $product->id,
                    'quantite_retiree' => $item->quantity,
                    'nouveau_stock' => $newStock
                ]);
            });
        }
    }
}
