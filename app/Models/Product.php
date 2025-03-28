<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 
        'code', 
        'selling_price', 
        'alert_quantity',
        'unit_id', 
        'category_id', 
        'quantity_in_stock', 
        'purchase_cost', 
        'cost_price', 
        'image', 
        'description'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function updateStock($quantity, $operation = 'add', $notes = '')
    {
        $oldQuantity = $this->quantity_in_stock;
        
        if ($operation === 'add') {
            $this->quantity_in_stock += $quantity;
        } elseif ($operation === 'subtract') {
            $this->quantity_in_stock -= $quantity;
        } elseif ($operation === 'set') {
            $this->quantity_in_stock = $quantity;
        }
        
        $this->save();
        
        // Enregistrer dans l'inventaire
        Inventory::create([
            'date' => now(),
            'product_id' => $this->id,
            'initial_stock' => $oldQuantity,
            'final_stock' => $this->quantity_in_stock,
            'notes' => $notes,
        ]);
        
        return $this;
    }
    
    public function checkStockAlert()
    {
        if ($this->quantity_in_stock <= $this->alert_quantity) {
            // Vous pouvez implémenter une notification ici
            return true;
        }
        return false;
    }
}