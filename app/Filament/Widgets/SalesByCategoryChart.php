<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\InvoiceItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesByCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Ventes par catégorie';

    protected function getData(): array
    {
        $data = InvoiceItem::select(
            'categories.name as category_name',
            DB::raw('SUM(invoice_items.quantity) as total_quantity'),
            DB::raw('SUM(invoice_items.subtotal) as total_amount')
        )
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('categories.name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Ventes par catégorie',
                    'data' => $data->pluck('total_amount')->toArray(),
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#8AC24A', '#607D8B', '#E91E63', '#9C27B0'
                    ],
                ],
            ],
            'labels' => $data->pluck('category_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bubble';
    }
}