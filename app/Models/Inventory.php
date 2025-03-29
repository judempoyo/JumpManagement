<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory;
    protected $fillable = [
        'date',
        'product_id',
        'initial_stock',
        'final_stock',
        'notes',
        'reference_type', // 'invoice', 'purchase_order', 'adjustment'
    'reference_id',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reference()
{
    return $this->morphTo('reference', 'reference_type', 'reference_id');
}

    
}