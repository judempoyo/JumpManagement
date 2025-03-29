<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    /* protected function handleRecordCreation(array $data): PurchaseOrder
{
    return DB::transaction(function () use ($data) {
        $order = PurchaseOrder::create($data);
        
        if (isset($data['items'])) {
            $order->items()->createMany($data['items']);
        }
        
        // Force le rechargement
        $order->load('items.product');
        
        return $order;
    });
}
    protected function afterCreate(): void
    {
        // Recharger les relations après création
        $this->record->refresh()->load('items.product');
        
        // Vous pouvez aussi logger pour vérifier
        \Log::debug('After create purchase order', [
            'items_count' => $this->record->items->count(),
            'items' => $this->record->items->toArray()
        ]);
    } */
}
