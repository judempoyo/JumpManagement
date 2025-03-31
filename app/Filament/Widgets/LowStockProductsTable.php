<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockProductsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereColumn('quantity_in_stock', '<=', 'alert_quantity')
                    ->orderBy('quantity_in_stock')
            )
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Stock actuel')
                    ->sortable(),

                Tables\Columns\TextColumn::make('alert_quantity')
                    ->label('Stock alerte')
                    ->sortable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Prix de vente')
                    ->money('XOF')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('approvisionner')
                    ->url(fn (Product $record) => route('filament.admin.resources.products.edit', $record))
                    ->icon('heroicon-o-plus'),
            ]);
    }
}