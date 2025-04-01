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
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categorie')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Stock actuel')
                    ->sortable(),

                Tables\Columns\TextColumn::make('alert_quantity')
                    ->label('Stock alerte')
                    ->sortable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Prix de vente')
                    ->money('CDF')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('approvisionner')
                    ->url(route('filament.admin.resources.purchase-orders.create'))
                    ->icon('heroicon-o-plus'),
            ])->defaultPaginationPageOption(5);
    }
}
