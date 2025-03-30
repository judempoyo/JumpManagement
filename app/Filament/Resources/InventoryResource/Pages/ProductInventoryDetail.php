<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;

use Filament\Resources\Pages\Page;
use App\Models\Product;
use Filament\Tables;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource;

class ProductInventoryDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = InventoryResource::class;
    protected static string $view = 'filament.resources.inventory-resource.pages.product-inventory-detail';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Détail Stock par Produit';
    protected static ?string $title = 'Analyse des Mouvements par Produit';
    protected static ?string $navigationGroup = 'Gestion des stocks';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->with(['category'])
                    ->withSum([
                        'inventories as total_entries' => function ($query) {
                            $query->where('movement_type', 'entry');
                        },
                        'inventories as total_exits' => function ($query) {
                            $query->where('movement_type', 'exit');
                        }
                    ], 'quantity')
                    ->with(['lastInventory'])
            )
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record) => $record->category?->name),
                    
                TextColumn::make('quantity_in_stock')
                    ->label('Stock Actuel')
                    ->numeric()
                    ->sortable()
                    ->color(function (Product $record) {
                        if ($record->quantity_in_stock <= $record->stock_alert) {
                            return 'danger';
                        }
                        return $record->quantity_in_stock > $record->stock_max ? 'warning' : 'success';
                    })
                    ->icon(function (Product $record) {
                        if ($record->quantity_in_stock <= $record->stock_alert) {
                            return 'heroicon-o-exclamation-triangle';
                        }
                        return null;
                    }),
                    
                TextColumn::make('total_entries')
                    ->label('Entrées')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->formatStateUsing(fn (Product $record) => Cache::remember(
                        "product_{$record->id}_entries_sum",
                        now()->addHours(6),
                        fn() => $record->total_entries ?: 0
                    )),
                    
                TextColumn::make('total_exits')
                    ->label('Sorties')
                    ->numeric()
                    ->sortable()
                    ->color('danger')
                    ->formatStateUsing(fn (Product $record) => Cache::remember(
                        "product_{$record->id}_exits_sum",
                        now()->addHours(6),
                        fn() => $record->total_exits ?: 0
                    )),
                    
                TextColumn::make('lastInventory.date')
                    ->label('Dernier Mouvement')
                    ->formatStateUsing(function ($state, Product $record) {
                        $lastMovement = Cache::remember(
                            "product_{$record->id}_last_movement",
                            now()->addHours(1),
                            fn() => $record->lastInventory
                        );
                        
                        if (!$lastMovement) return 'Aucun';
                        
                        return sprintf(
                            "%s - %s%s | Stock: %s → %s",
                            $lastMovement->date->format('d/m/Y'),
                            $lastMovement->movement_type === 'entry' ? '+' : '-',
                            $lastMovement->quantity,
                            $lastMovement->stock_before,
                            $lastMovement->stock_after
                        );
                    })
                    ->color(fn (Product $record) => 
                        Cache::get("product_{$record->id}_last_movement")?->movement_type === 'entry' 
                            ? 'success' 
                            : 'danger')
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderBy(
                            Inventory::select('date')
                                ->whereColumn('product_id', 'products.id')
                                ->latest()
                                ->limit(1),
                            $direction
                        );
                    }),
            ])
            ->filters([
                // Vos filtres ici
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->url(fn (Product $record): string => InventoryResource::getUrl('index', [
                            'tableFilters' => [
                                'product' => ['value' => $record->id],
                                'date_range' => [
                                    'from' => now()->subYear()->format('Y-m-d'),
                                    'to' => now()->format('Y-m-d')
                                ]
                            ]
                        ])),
                        
                    EditAction::make()
                        ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record])),
                        
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                // Actions groupées si nécessaire
            ])
            ->emptyStateHeading('Aucun produit trouvé')
            ->defaultSort('quantity_in_stock', 'desc')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public function getDefaultTableSortColumn(): ?string
    {
        return 'name';
    }
    
    public function getDefaultTableSortDirection(): ?string
    {
        return 'asc';
    }
}