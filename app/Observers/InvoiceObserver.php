<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Product;

class InvoiceObserver
{
    public function created(Invoice $invoice)
    {
        foreach ($invoice->items as $item) {
            $product = $item->product;
            $product->quantity_in_stock -= $item->quantity;
            $product->save();
        }
    }
    
    public function updating(Invoice $invoice)
    {
        if ($invoice->isDirty('paid') && $invoice->paid) {
            $invoice->createReceivable();
        }
    }
}