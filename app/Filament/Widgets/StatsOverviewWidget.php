<?php


namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Produits en stock', Product::count())
                ->description('Total des produits disponibles')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->url(route('filament.admin.resources.products.index')),

            Stat::make('Stock faible', Product::whereColumn('quantity_in_stock', '<=', 'alert_quantity')->count())
                ->description('Produits nécessitant réapprovisionnement')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url(route('filament.admin.resources.products.index', [
                    'tableFilters' => [
                        'low_stock' => [
                            'isActive' => true,
                        ],
                    ],
                ])),

            Stat::make('Ventes aujourd\'hui', Invoice::whereDate('date', today())->count())
                ->description('Factures créées aujourd\'hui')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->url(route('filament.admin.resources.invoices.index')),

            Stat::make('Chiffre d\'affaires', number_format(Invoice::whereDate('date', today())->sum('total'), 0, ',', ' ') . ' FCFA')
                ->description('Total des ventes aujourd\'hui')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Commandes fournisseurs', PurchaseOrder::where('status', '!=', 'delivered')->count())
                ->description('Commandes en attente')
                ->descriptionIcon('heroicon-o-truck')
                ->color('warning')
                ->url(route('filament.admin.resources.purchase-orders.index')),

            Stat::make('Clients enregistrés', Customer::count())
                ->description('Total des clients')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->url(route('filament.admin.resources.customers.index')),
        ];
    }
}