<?php

namespace App\Filament\Resources\AdjustmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryRelationManager extends RelationManager
{
    protected static string $relationship = 'inventory';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('initial_stock')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('final_stock')
                    ->required()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('initial_stock'),
                Tables\Columns\TextColumn::make('final_stock'),
                Tables\Columns\TextColumn::make('notes'),
            ])
            ->filters([
                //
            ]);
    }
}