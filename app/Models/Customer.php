<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'adress',
        'customer_name',
    ];

   
public function customer()
{
    return $this->belongsTo(Customer::class)->withDefault([
        'name' => $this->customer_name ?? 'Client passager',
        'phone' => '',
        'email' => '',
        'address' => '',
    ]);
}
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    

}
