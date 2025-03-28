<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'financial_entry_id', 'amount', 'payment_date',
        'payment_method', 'reference', 'notes', 'user_id'
    ];

    // Relation avec l'entrée financière
    public function financialEntry()
    {
        return $this->belongsTo(FinancialEntry::class);
    }

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Callback après création/mise à jour/suppression
    protected static function booted()
    {
        static::saved(function ($payment) {
            $payment->financialEntry->updateBalance();
        });

        static::deleted(function ($payment) {
            $payment->financialEntry->updateBalance();
        });
    }
}