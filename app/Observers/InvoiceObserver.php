<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Inventory;

class InvoiceObserver
{
    /**
     * Lorsqu'une facture est créée
     */
    public function created(Invoice $invoice)
    {
        // Diminuer les stocks pour chaque article vendu
        foreach ($invoice->items as $item) {
            $product = $item->product;
            $newStock = max(0, $product->quantity_in_stock - $item->quantity);
            
            // Historique avant modification
            $initialStock = $product->quantity_in_stock;
            
            $product->quantity_in_stock = $newStock;
            $product->save();

            // Enregistrer dans l'historique des stocks
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $initialStock,
                'final_stock' => $newStock,
                'notes' => 'Vente facture #' . $invoice->id . ($invoice->delivered ? ' (livrée)' : ''),
            ]);
        }

        // Si la facture est marquée comme payée directement
        if ($invoice->paid) {
            $invoice->createReceivable();
        }
    }

    /**
     * Lorsqu'une facture est mise à jour
     */
    public function updated(Invoice $invoice)
    {
        // Gestion du paiement
        if ($invoice->isDirty('paid') && $invoice->paid) {
            $invoice->createReceivable();
        }

        // Gestion de la livraison
        if ($invoice->isDirty('delivered') && $invoice->delivered) {
            foreach ($invoice->items as $item) {
                // Marquer comme livré dans l'historique
                Inventory::where('notes', 'like', '%Vente facture #' . $invoice->id . '%')
                    ->update(['notes' => 'Vente facture #' . $invoice->id . ' (livrée)']);
            }
        }

        // Si modification des quantités (cas complexe)
        if ($invoice->isDirty()) {
            $originalItems = $invoice->getOriginal('items');
            $currentItems = $invoice->items;

            // Comparer les anciennes et nouvelles quantités
            foreach ($currentItems as $item) {
                $originalItem = collect($originalItems)->firstWhere('id', $item->id);
                
                if ($originalItem && $originalItem->quantity != $item->quantity) {
                    $difference = $originalItem->quantity - $item->quantity;
                    $product = $item->product;
                    
                    // Ajuster le stock
                    $product->quantity_in_stock += $difference;
                    $product->save();

                    // Mettre à jour l'historique
                    Inventory::create([
                        'date' => now(),
                        'product_id' => $product->id,
                        'initial_stock' => $product->quantity_in_stock - $difference,
                        'final_stock' => $product->quantity_in_stock,
                        'notes' => 'Ajustement facture #' . $invoice->id,
                    ]);
                }
            }
        }
    }

    /**
     * Lorsqu'une facture est supprimée ou annulée
     */
    public function deleted(Invoice $invoice)
    {
        // Restaurer les stocks
        foreach ($invoice->items as $item) {
            $product = $item->product;
            $initialStock = $product->quantity_in_stock;
            
            $product->quantity_in_stock += $item->quantity;
            $product->save();

            // Enregistrer l'annulation dans l'historique
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'initial_stock' => $initialStock,
                'final_stock' => $product->quantity_in_stock,
                'notes' => 'Annulation facture #' . $invoice->id,
            ]);
        }

        // Supprimer les entrées financières liées
        $invoice->financialEntries()->delete();
    }
}