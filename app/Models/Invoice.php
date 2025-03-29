<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_name',
        'date',
        'time',
        'total',
        'amount_payable',
        'discount',
        'status',
        'user_id',
        'paid',
        'delivered',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];
    public function customer()
{
    return $this->belongsTo(Customer::class)->withDefault([
        'name' => $this->customer_name ?? 'Client passager',
        'phone' => 'Non spécifié',
        'email' => 'Non spécifié',
        'adress' => 'Non spécifié',
    ]);
}
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->with('product');
    
    }
   
    
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function financialEntries()
{
    return $this->morphMany(FinancialEntry::class, 'sourceDocument');
}
/* 
public function createReceivable()
{
    return $this->financialEntries()->create([
        'type' => 'receivable',
        'total_amount' => $this->amount_payable,
        'remaining_amount' => $this->amount_payable,
        'start_date' => now(),
        'partner_id' => $this->customer_id,
        'partner_type' => Customer::class,
    ]);
} */
}