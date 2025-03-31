<?php

use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder; 
use App\Models\Product;

class LowStockProductsTable extends TableWidget
{
    protected function getTableQuery(): Builder
    {
        return Product::whereColumn('quantity_in_stock', '<=', 'alert_quantity')
            ->with('category')
            ->orderBy('quantity_in_stock');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->searchable(),
            Tables\Columns\TextColumn::make('category.name')
                ->label('Catégorie'),
            Tables\Columns\TextColumn::make('quantity_in_stock')
                ->label('Stock Actuel'),
            Tables\Columns\TextColumn::make('alert_quantity')
                ->label('Seuil d\'Alerte'),
        ];
    }
}