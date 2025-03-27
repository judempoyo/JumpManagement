<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'adress',
    ];
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}