<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialEntry extends Model
{
    protected $fillable = [
        'type', 'total_amount', 'remaining_amount', 'start_date', 'due_date',
        'is_paid', 'source_document_id', 'source_document_type',
        'partner_id', 'partner_type', 'notes'
    ];

    // Relation polymorphique avec le document source
    public function sourceDocument()
    {
        return $this->morphTo();
    }

    // Relation polymorphique avec le partenaire (client ou fournisseur)
    public function partner()
    {
        return $this->morphTo();
    }

    // Relation avec les paiements
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Méthode pour mettre à jour le solde restant
    public function updateBalance()
    {
        $paidAmount = $this->payments()->sum('amount');
        $this->remaining_amount = $this->total_amount - $paidAmount;
        $this->is_paid = $this->remaining_amount <= 0;
        $this->save();
    }

    // Scopes pour filtrer facilement
    public function scopeDebts($query)
    {
        return $query->where('type', 'debt');
    }

    public function scopeReceivables($query)
    {
        return $query->where('type', 'receivable');
    }
}