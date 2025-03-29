<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $fillable = ['quantity', 'type', 'notes']; // type: 'add'/'remove'
    
    public function inventory()
    {
        return $this->morphOne(Inventory::class, 'reference');
    }
}
