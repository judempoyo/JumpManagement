<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations générales')
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Fournisseur')
                            ->required()
                            ->options(Supplier::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $supplier = Supplier::find($state);
                                if ($supplier) {
                                    $set('supplier_name', $supplier->name);
                                    $set('supplier_phone', $supplier->phone);
                                    $set('supplier_email', $supplier->email);
                                    $set('supplier_adress', $supplier->adress);
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
                            
                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(3),
                
                Section::make('Détails du fournisseur')
                    ->schema([
                        TextInput::make('supplier_name')
                            ->label('Nom')
                            ->disabled()
                            ->dehydrated(false),
                            
                        TextInput::make('supplier_phone')
                            ->label('Téléphone')
                            ->disabled()
                            ->dehydrated(false),
                            
                        TextInput::make('supplier_email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                            
                        TextInput::make('supplier_adress')
                            ->label('Adresse')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),
                    ])->columns(3)
                    ->visible(fn (Get $get): bool => filled($get('supplier_id'))),
                
                Section::make('Articles commandés')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produit')
                                    ->required()
                                    ->options(Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('unit_price', $product->purchase_cost);
                                            $set('product_name', $product->name);
                                            $set('product_code', $product->code);
                                        }
                                    }),
                                    
                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateItemTotals($get, $set);
                                    }),
                                    
                                TextInput::make('unit_price')
                                    ->label('Prix unitaire')
                                    ->required()
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateItemTotals($get, $set);
                                    }),
                                    
                                TextInput::make('subtotal')
                                    ->label('Sous-total')
                                    ->readOnly()
                                    ->numeric()
                                    ->dehydrated(false),
                                    
                                // Champs cachés pour affichage seulement
                                TextInput::make('product_name')
                                    ->label('Nom produit')
                                    ->disabled()
                                    ->dehydrated(false),
                                    
                                TextInput::make('product_code')
                                    ->label('Code produit')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->columns(5)
                            ->columnSpanFull()
                            ->itemLabel(fn (array $state): ?string => $state['product_name'] ?? null)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateOrderTotals($get, $set);
                            }),
                    ]),
                
                Section::make('Totaux')
                    ->schema([
                        TextInput::make('discount')
                            ->label('Remise')
                            ->numeric()
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateOrderTotals($get, $set);
                            }),
                            
                        TextInput::make('total')
                            ->label('Total brut')
                            ->readOnly()
                            ->numeric()
                            ->dehydrated(false),
                            
                        TextInput::make('amount_payable')
                            ->label('Net à payer')
                            ->readOnly()
                            ->numeric()
                            ->dehydrated(false),
                    ])->columns(3),
            ]);
    }

    protected static function updateItemTotals(Get $get, Set $set): void
    {
        $quantity = (float) $get('quantity');
        $unitPrice = (float) $get('unit_price');
        
        $subtotal = $quantity * $unitPrice;
        
        $set('subtotal', number_format($subtotal, 2, '.', ''));
    }

    protected static function updateOrderTotals(Get $get, Set $set): void
    {
        $items = $get('items');
        $discount = (float) $get('discount') ?? 0;
        
        $total = collect($items)->reduce(function ($carry, $item) {
            return $carry + ((float) $item['subtotal'] ?? 0);
        }, 0);
        
        $amountPayable = $total - $discount;
        
        $set('total', number_format($total, 2, '.', ''));
        $set('amount_payable', number_format($amountPayable, 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('time')
                    ->label('Heure')
                    ->time(),
                    
                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('amount_payable')
                    ->label('Net à payer')
                    ->money('USD'),
                    
                BadgeColumn::make('paid')
                    ->label('Statut paiement')
                    ->colors([
                        'danger' => false,
                        'success' => true,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Payé' : 'Impayé'),
                    
                BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->label('Fournisseur'),
                    
                Tables\Filters\Filter::make('paid')
                    ->label('Payé seulement')
                    ->query(fn (Builder $query): Builder => $query->where('paid', true)),
                    
                Tables\Filters\Filter::make('unpaid')
                    ->label('Impayé seulement')
                    ->query(fn (Builder $query): Builder => $query->where('paid', false)),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Brouillon',
                        'completed' => 'Complété',
                        'cancelled' => 'Annulé',
                    ])
                    ->label('Statut'),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-download')
                    ->url(fn (PurchaseOrder $record) => route('purchase-orders.pdf', $record))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
