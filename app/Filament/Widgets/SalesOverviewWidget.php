<?php

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Product;

class SalesOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Ventes aujourd\'hui', Invoice::whereDate('date', today())->sum('total'))
                ->description('Montant total des ventes du jour')
                ->color('success'),
                
            Stat::make('Commandes en attente', PurchaseOrder::where('status', 'pending')->count())
                ->description('Commandes fournisseurs non livrées')
                ->color('warning'),
                
            Stat::make('Stock critique', Product::whereColumn('quantity_in_stock', '<=', 'alert_quantity')->count())
                ->description('Produits nécessitant réapprovisionnement')
                ->color('danger'),
        ];
    }
}