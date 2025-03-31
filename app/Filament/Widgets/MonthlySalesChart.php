<?php

use Filament\Widgets\LineChartWidget;
use App\Models\Invoice;

class MonthlySalesChart extends LineChartWidget
{
    Public function getHeading(): string
    {
        return 'Évolution des Ventes Mensuelles';
    }

    protected function getData(): array
    {
        $sales = Invoice::selectRaw('MONTH(date) as month, SUM(total) as total')
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Ventes',
                    'data' => array_values($sales),
                    'backgroundColor' => '#4CAF50',
                ],
            ],
            'labels' => array_map(fn($m) => date('F', mktime(0, 0, 0, $m, 1)), array_keys($sales)),
        ];
    }
}