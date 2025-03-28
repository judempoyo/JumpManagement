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
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}