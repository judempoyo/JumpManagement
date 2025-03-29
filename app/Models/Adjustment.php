<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'type', // 'add' ou 'remove'
        'reason'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->morphOne(Inventory::class, 'reference');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}