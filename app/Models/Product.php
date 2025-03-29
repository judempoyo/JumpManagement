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

    public function updateStock($quantity, $operation = 'add', $notes = '', $referenceType = null, $referenceId = null)
{
    return \App\Services\StockManager::updateStock(
        $this, 
        $quantity, 
        $operation, 
        $notes,
        $referenceType,
        $referenceId
    );
}
public function getStockHistory($period = null)
{
    $query = $this->inventories()->orderBy('date', 'desc');
    
    if ($period) {
        $query->where('date', '>=', now()->subDays($period));
    }
    
    return $query->get();
}

public function getCurrentStockValue()
{
    return $this->quantity_in_stock * $this->cost_price;
}

public function checkStockAlert()
{
    if ($this->quantity_in_stock <= $this->alert_quantity) {
        // Envoyer une notification
        // \App\Events\LowStockAlert::dispatch($this);
        return true;
    }
    return false;
}
}