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
use Filament\Forms\Components\ToggleButtons;
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
                                    $set('stock_before', $product->quantity_in_stock);
                                    $set('product_name', $product->name);
                                    $set('product_code', $product->code);
                                }
                            }),

                        ToggleButtons::make('movement_type')
                            ->label('Type de mouvement')
                            ->required()
                            ->options([
                                'entry' => 'Entrée',
                                'exit' => 'Sortie',
                            ])
                            ->colors([
                                'entry' => 'success',
                                'exit' => 'danger',
                            ])
                            ->inline(),

                        TextInput::make('quantity')
                            ->label('Quantité')
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01),

                        TextInput::make('stock_before')
                            ->label('Stock avant')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('stock_after')
                            ->label('Stock après')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get) {
                                $this->calculateStockAfter($set, $get);
                            }),
                    ])
                    ->columns(3),

                Section::make('Détails supplémentaires')
                    ->schema([
                        Select::make('reference_type')
                            ->label('Type de référence')
                            ->options([
                                'invoice' => 'Facture',
                                'purchase_order' => 'Bon de commande',
                                'adjustment' => 'Ajustement',
                            ])
                            ->searchable(),

                        TextInput::make('reference_id')
                            ->label('ID Référence')
                            ->numeric(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ]),
            ])
            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                $this->calculateStockAfter($set, $get);
            });
    }

    protected static function calculateStockAfter(Forms\Set $set, Forms\Get $get): void
    {
        $stockBefore = (float) $get('stock_before');
        $quantity = (float) $get('quantity');
        $movementType = $get('movement_type');

        $stockAfter = $movementType === 'entry'
            ? $stockBefore + $quantity
            : $stockBefore - $quantity;

        $set('stock_after', $stockAfter);
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

                BadgeColumn::make('movement_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => $state === 'entry' ? 'Entrée' : 'Sortie')
                    ->colors([
                        'success' => 'entry',
                        'danger' => 'exit',
                    ]),

                TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('stock_before')
                    ->label('Stock avant')
                    ->sortable(),

                TextColumn::make('stock_after')
                    ->label('Stock après')
                    ->sortable(),

                TextColumn::make('reference_type')
                    ->label('Référence')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'invoice' => 'Facture',
                            'purchase_order' => 'Bon de commande',
                            'adjustment' => 'Ajustement',
                            default => $state,
                        };
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

                SelectFilter::make('movement_type')
                    ->label('Type de mouvement')
                    ->options([
                        'entry' => 'Entrées',
                        'exit' => 'Sorties',
                    ]),

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
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        // Mettre à jour le stock du produit après modification
                        $record->product->update([
                            'quantity_in_stock' => $record->stock_after
                        ]);
                    }),

                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Réajuster le stock si l'inventaire est supprimé
                        $diff = $record->movement_type === 'entry'
                            ? -$record->quantity
                            : $record->quantity;

                        $record->product->increment('quantity_in_stock', $diff);
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
            'quantity_in_stock' => $record->stock_after
        ]);
    }
}
