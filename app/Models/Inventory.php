<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'date',
        'product_id',
        'initial_stock',
        'final_stock',
        'notes',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}