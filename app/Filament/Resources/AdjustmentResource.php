<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdjustmentResource\Pages;
use App\Filament\Resources\AdjustmentResource\RelationManagers;
use App\Models\Adjustment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class AdjustmentResource extends Resource
{
    protected static ?string $model = Adjustment::class;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $modelLabel = 'Ajustement';

    protected static ?string $navigationLabel = 'Ajustements';

    protected static ?string $navigationGroup = 'Gestion des stocks';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $product = \App\Models\Product::find($state);
                        if ($product) {
                            $set('current_stock', $product->quantity_in_stock);
                        }
                    }),

                Forms\Components\TextInput::make('current_stock')
                    ->label('Stock actuel')
                    ->numeric()
                    ->readOnly()
                    ->default(function () {
                        return request()->has('product_id')
                            ? \App\Models\Product::find(request('product_id'))->quantity_in_stock
                            : 0;
                    }),

                Forms\Components\Radio::make('type')
                    ->options([
                        'add' => 'Ajout stock',
                        'remove' => 'Retrait stock',
                    ])
                    ->required()
                    ->inline(),

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(function (Forms\Get $get) {
                        return $get('type') === 'remove'
                            ? \App\Models\Product::find($get('product_id'))?->quantity_in_stock
                            : null;
                    }),

                Forms\Components\Textarea::make('reason')
                    ->label('Raison')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\DatePicker::make('created_at')
                    ->label('Date')
                    ->default(now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'add' => 'Ajout',
                        'remove' => 'Retrait',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'add' => 'success',
                        'remove' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Raison')
                    ->limit(30),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(),
            ])
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make()->fromTable()->except([
                        'created_at', 'updated_at', 'deleted_at',
                    ]),]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'add' => 'Ajouts',
                        'remove' => 'Retraits',
                    ]),

                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InventoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdjustments::route('/'),
            'create' => Pages\CreateAdjustment::route('/create'),
            'edit' => Pages\EditAdjustment::route('/{record}/edit'),
        ];
    }
}
