<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'supplier_id',
        'date',
        'time',
        'total',
        'amount_payable',
        'discount',
        'status',
        'user_id',
        'paid',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function items()
{
    return $this->hasMany(PurchaseOrderItem::class)->with('product');
}
    public function financialEntries()
    {
        return $this->morphMany(FinancialEntry::class, 'sourceDocument');
    }
    
    public function createDebt()
    {
        return $this->financialEntries()->create([
            'type' => 'debt',
            'total_amount' => $this->amount_payable,
            'remaining_amount' => $this->amount_payable,
            'start_date' => now(),
            'partner_id' => $this->supplier_id,
            'partner_type' => Supplier::class,
        ]);
    }

    protected static function booted()
    {
        static::created(function ($purchaseOrder) {
            foreach ($purchaseOrder->items as $item) {
                $product = $item->product;
                $product->updateStock(
                    $item->quantity,
                    'add',
                    "Réception de commande fournisseur #{$purchaseOrder->id}"
                );
                $product->checkStockAlert();
            }
        });

        static::updated(function ($purchaseOrder) {
            // Gérer les modifications si nécessaire
        });

        static::deleted(function ($purchaseOrder) {
            foreach ($purchaseOrder->items as $item) {
                $product = $item->product;
                $product->updateStock(
                    $item->quantity,
                    'subtract',
                    "Annulation commande fournisseur #{$purchaseOrder->id}"
                );
            }
        });
    }
}