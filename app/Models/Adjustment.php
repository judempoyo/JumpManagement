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

    public function inventory()
    {
        return $this->morphOne(Inventory::class, 'reference');
    }
}