<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class StockManager
{
    public static function updateStock(Product $product, $quantity, $operation, $notes, $referenceType = null, $referenceId = null)
    {
        return DB::transaction(function () use ($product, $quantity, $operation, $notes, $referenceType, $referenceId) {
            $oldQuantity = $product->quantity_in_stock;
            
            switch ($operation) {
                case 'add':
                    $product->quantity_in_stock += $quantity;
                    break;
                case 'subtract':
                    if ($product->quantity_in_stock < $quantity) {
                        throw new \Exception("Stock insuffisant pour le produit {$product->name}");
                    }
                    $product->quantity_in_stock -= $quantity;
                    break;
                case 'set':
                    $product->quantity_in_stock = $quantity;
                    break;
            }
            
            $product->save();
            
            Inventory::create([
                'date' => now(),
                'product_id' => $product->id,
                'quantity' => self::calculateMovementQuantity($operation, $quantity, $oldQuantity, $product->quantity_in_stock),
                'initial_stock' => $oldQuantity,
                'final_stock' => $product->quantity_in_stock,
                'notes' => $notes,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
            
            $product->checkStockAlert();
            
            return $product;
        });
    }
    
    protected static function calculateMovementQuantity($operation, $quantity, $oldQuantity, $newQuantity)
    {
        return match($operation) {
            'add' => $quantity,
            'subtract' => -$quantity,
            'set' => $newQuantity - $oldQuantity,
            default => 0
        };
    }
}