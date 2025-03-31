<?php

namespace App\Filament\Widgets;

use App\Models\Inventory;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentInventoryMovementsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Inventory::query()->with('product')->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('movement_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entry' => 'success',
                        'exit' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entry' => 'Entrée',
                        'exit' => 'Sortie',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_before')
                    ->label('Stock avant'),

                Tables\Columns\TextColumn::make('stock_after')
                    ->label('Stock après'),
            ]);
    }
}