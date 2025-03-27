<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Customer;
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

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Facture';

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations générales')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Client')
                            ->required()
                            ->options(Customer::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $customer = Customer::find($state);
                                if ($customer) {
                                    $set('customer_name', $customer->name);
                                    $set('customer_phone', $customer->phone);
                                    $set('customer_email', $customer->email);
                                    $set('customer_adress', $customer->adress);
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
                            
                        Toggle::make('delivered')
                            ->label('Livré')
                            ->inline(false),
                            
                        Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'sent' => 'Envoyée',
                                'paid' => 'Payée',
                                'cancelled' => 'Annulée',
                            ])
                            ->default('draft'),
                            
                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(3),
                
                Section::make('Détails du client')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nom')
                            ->disabled()
                            ->dehydrated(false),
                            
                        TextInput::make('customer_phone')
                            ->label('Téléphone')
                            ->disabled()
                            ->dehydrated(false),
                            
                        TextInput::make('customer_email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                            
                        TextInput::make('customer_adress')
                            ->label('Adresse')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(2),
                    ])->columns(3)
                    ->visible(fn (Get $get): bool => filled($get('customer_id'))),
                
                Section::make('Articles facturés')
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
                                            $set('unit_price', $product->selling_price);
                                            $set('product_name', $product->name);
                                            $set('product_code', $product->code);
                                        }
                                    }),
                                    
                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
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
                                self::updateInvoiceTotals($get, $set);
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
                                self::updateInvoiceTotals($get, $set);
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

    protected static function updateInvoiceTotals(Get $get, Set $set): void
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
                TextColumn::make('customer.name')
                    ->label('Client')
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
                    ->label('Paiement')
                    ->colors([
                        'danger' => false,
                        'success' => true,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Payé' : 'Impayé'),
                    
                BadgeColumn::make('delivered')
                    ->label('Livraison')
                    ->colors([
                        'danger' => false,
                        'success' => true,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Livré' : 'Non livré'),
                    
                BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'sent',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->label('Client'),
                    
                Tables\Filters\Filter::make('paid')
                    ->label('Payé seulement')
                    ->query(fn (Builder $query): Builder => $query->where('paid', true)),
                    
                Tables\Filters\Filter::make('unpaid')
                    ->label('Impayé seulement')
                    ->query(fn (Builder $query): Builder => $query->where('paid', false)),
                    
                Tables\Filters\Filter::make('delivered')
                    ->label('Livré seulement')
                    ->query(fn (Builder $query): Builder => $query->where('delivered', true)),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyée',
                        'paid' => 'Payée',
                        'cancelled' => 'Annulée',
                    ])
                    ->label('Statut'),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-download')
                    ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Marquer comme payé')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (Invoice $record) {
                        $record->update(['paid' => true, 'status' => 'paid']);
                        $record->createReceivable();
                    })
                    ->visible(fn (Invoice $record): bool => !$record->paid),
                    
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
