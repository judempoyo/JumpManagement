<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'customer_name',
    ];

   
/* public function customer()
{
    return $this->belongsTo(Customer::class)->withDefault([
        'name' => $this->customer_name ?? 'Client passager',
        'phone' => '',
        'email' => '',
        'address' => '',
    ]);
} */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    

}
