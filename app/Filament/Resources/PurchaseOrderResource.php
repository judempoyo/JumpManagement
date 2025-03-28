<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Inventory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Number;



class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Bon de commande';

    protected static ?string $navigationLabel = 'Bons de commande';

    protected static ?string $navigationGroup = 'Achats';

    protected static ?int $navigationSort = 1;
    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}   

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de base')
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Fournisseur')
                            ->options(Supplier::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $supplier = Supplier::find($state);
                                if ($supplier) {
                                    $set('supplier_name', $supplier->name);
                                }
                            }),
                            
                        DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now()),
                            
                        TimePicker::make('time')
                            ->label('Heure')
                            ->required()
                            ->default(now()),
                            
                        Toggle::make('paid')
                            ->label('Payé')
                            ->inline(false),
                            
                        TextInput::make('discount')
                            ->label('Remise')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id())
                            ->required(),
                            
                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Articles')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                ->label('Produit')
                                ->options(function (Get $get) {
                                    // Récupérer les produits déjà sélectionnés
                                    $selectedProducts = collect($get('../../items'))
                                        ->pluck('product_id')
                                        ->filter()
                                        ->toArray();
                                    
                                    // Exclure les produits déjà sélectionnés
                                    return Product::query()
                                        ->whereNotIn('id', $selectedProducts)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('unit_price', $product->purchase_cost);
                                    }
                                }),
                                    
                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateItemSubtotal($get, $set);
                                    }),
                                    
                                TextInput::make('unit_price')
                                    ->label('Prix unitaire')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateItemSubtotal($get, $set);
                                    }),
                                    
                                TextInput::make('subtotal')
                                    ->label('Sous-total')
                                    ->prefix('$')
                                    ->readOnly()
                                    ->numeric(),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => Product::find($state['product_id'])?->name ?? null)
                            ->addActionLabel('Ajouter un article')
                            ->minItems(1)
                            ->reorderable()
                            ->cloneable()
                            ->collapsible(),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        // S'assurer qu'un produit n'est pas ajouté plusieurs fois
                        $productIds = array_column($data['items'] ?? [], 'product_id');
                        if (count($productIds) !== count(array_unique($productIds))) {
                            throw new \Exception('Un produit ne peut être ajouté qu\'une seule fois');
                        }
                        return $data;
                    }),
                    
                Forms\Components\Section::make('Résumé')
                    ->schema([
                        TextInput::make('total')
                            ->label('Total')
                            ->prefix('$')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                            
                        TextInput::make('amount_payable')
                            ->label('Montant à payer')
                            ->prefix('$')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    protected static function updateItemSubtotal(Get $get, Set $set): void
    {
        $quantity = $get('quantity');
        $unitPrice = $get('unit_price');
        
        if ($quantity && $unitPrice) {
            $subtotal = $quantity * $unitPrice;
            $set('subtotal', number_format($subtotal, 2, '.', ''));
            
            // Update order totals
            $items = $get('../../items');
            $total = collect($items)->sum('subtotal');
            $discount = $get('discount') ?? 0;
            $amountPayable = $total - $discount;
            
            $set('../../total', number_format($total, 2, '.', ''));
            $set('../../amount_payable', number_format($amountPayable, 2, '.', ''));
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('paid')
                    ->label('Payé')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
                    
                Tables\Filters\Filter::make('paid')
                    ->query(fn (Builder $query): Builder => $query->where('paid', true))
                    ->label('Payés uniquement'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
               Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (PurchaseOrder $record) => route('purchase-orders.pdf', $record))
                    ->openUrlInNewTab(), 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}