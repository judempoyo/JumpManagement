<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'quantity',
        'unit_price',
        'subtotal',
        'purchase_order_id',
        'product_id',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}