<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    

}
