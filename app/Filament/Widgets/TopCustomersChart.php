<?php

use Filament\Widgets\BarChartWidget;
use App\Models\Customer;

class TopCustomersChart extends BarChartWidget
{
    public function getHeading(): string
    {
        return 'Top 5 des Clients par Chiffre d\'Affaires';
    }

    protected function getData(): array
    {
        $customers = Customer::withSum('invoices', 'total')
            ->orderByDesc('invoices_sum_total')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'CA Total',
                    'data' => $customers->pluck('invoices_sum_total')->toArray(),
                    'backgroundColor' => '#2196F3',
                ]
            ],
            'labels' => $customers->pluck('name')->toArray()
        ];
    }
}