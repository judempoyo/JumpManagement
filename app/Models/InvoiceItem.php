<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'quantity',
        'unit_price',
        'subtotal',
        'invoice_id',
        'product_id',
    ];

    protected $casts = [
        'quantity' => 'integer'
    ];
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
}