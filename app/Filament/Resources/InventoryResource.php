<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Carbon;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $modelLabel = 'Inventaire';

    protected static ?string $navigationLabel = 'Inventaires';

    protected static ?string $navigationGroup = 'Gestion des stocks';

    protected static ?int $navigationSort = 3;
    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de base')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                            
                        Select::make('product_id')
                            ->label('Produit')
                            ->required()
                            ->options(Product::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    $set('initial_stock', $product->quantity_in_stock);
                                    $set('product_name', $product->name);
                                    $set('product_code', $product->code);
                                }
                            }),
                            
                        TextInput::make('initial_stock')
                            ->label('Stock initial')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),
                            
                        TextInput::make('final_stock')
                            ->label('Stock final')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                $initial = (float) $get('initial_stock');
                                $final = (float) $get('final_stock');
                                $difference = $final - $initial;
                                $set('difference', $difference);
                            }),
                            
                        TextInput::make('difference')
                            ->label('Différence')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(3),
                
                Section::make('Détails supplémentaires')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('product.code')
                    ->label('Code')
                    ->searchable(),
                    
                TextColumn::make('initial_stock')
                    ->label('Stock initial')
                    ->sortable(),
                    
                TextColumn::make('final_stock')
                    ->label('Stock final')
                    ->sortable(),
                    
                TextColumn::make('difference')
                    ->label('Différence')
                    ->numeric()
                    ->color(function ($record) {
                        $diff = $record->final_stock - $record->initial_stock;
                        return $diff > 0 ? 'success' : ($diff < 0 ? 'danger' : 'gray');
                    })
                    ->formatStateUsing(function ($record) {
                        $diff = $record->final_stock - $record->initial_stock;
                        return ($diff > 0 ? '+' : '') . $diff;
                    }),
                    
                BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'correct',
                        'danger' => 'discrepancy',
                    ])
                    ->formatStateUsing(function ($record) {
                        return $record->final_stock == $record->initial_stock ? 'Correct' : 'Écart';
                    }),
                    
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    }),
            ])
            ->filters([
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->label('Produit')
                    ->searchable()
                    ->preload(),
                    
                Filter::make('date_range')
                    ->label('Période')
                    ->form([
                        DatePicker::make('from')
                            ->label('Du')
                            ->default(now()->subMonth()),
                        DatePicker::make('to')
                            ->label('Au')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Du ' . Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['to'] ?? null) {
                            $indicators['to'] = 'Au ' . Carbon::parse($data['to'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                    
                Filter::make('discrepancies')
                    ->label('Avec écarts seulement')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('final_stock', '!=', 'initial_stock'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        // Mettre à jour le stock du produit après modification
                        if ($record->wasChanged('final_stock')) {
                            $record->product->update([
                                'quantity_in_stock' => $record->final_stock
                            ]);
                        }
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Réajuster le stock si l'inventaire est supprimé
                        $diff = $record->final_stock - $record->initial_stock;
                        $record->product->decrement('quantity_in_stock', $diff);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
    
    public static function afterCreate($record)
    {
        // Mettre à jour le stock du produit après création
        $record->product->update([
            'quantity_in_stock' => $record->final_stock
        ]);
    }
}