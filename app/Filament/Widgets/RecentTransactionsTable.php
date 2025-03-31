<?php

use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Inventory;


class RecentTransactionsTable extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Inventory::with('product')->latest()->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('date')
                ->dateTime(),
            Tables\Columns\TextColumn::make('product.name')
                ->label('Produit'),
            Tables\Columns\TextColumn::make('movement_type')
                ->label('Type')
                ->formatStateUsing(fn($state) => ucfirst($state)),
            Tables\Columns\TextColumn::make('quantity')
                ->label('Quantité'),
            Tables\Columns\TextColumn::make('stock_after')
                ->label('Stock Final'),
        ];
    }
}