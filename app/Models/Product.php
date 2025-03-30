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

    protected $casts = [
        'quantity_in_stock' => 'integer',
        'alert_quantity' => 'integer'
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

    public function adjustments()
    {
        return $this->hasMany(Adjustment::class);
    }

    public function lastInventory()
{
    return $this->hasOne(Inventory::class)->latestOfMany();
}
   /*  public function recalculateStock()
    {
        $this->quantity_in_stock = $this->inventories()
            ->latest('date')
            ->value('final_stock') ?? 0;

        $this->save();
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
    } */

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