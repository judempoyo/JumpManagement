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
}