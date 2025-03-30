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
        'quantity',
        'movement_type', // 'entry' ou 'exit'
        'notes',
        'reference_type', // 'invoice', 'purchase_order', 'adjustment'
        'reference_id',
        'stock_before',
        'stock_after',
    ];

     /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    // Scopes pour faciliter les requêtes
    public function scopeEntries($query)
    {
        return $query->where('movement_type', 'entry');
    }

    public function scopeExits($query)
    {
        return $query->where('movement_type', 'exit');
    }


}
