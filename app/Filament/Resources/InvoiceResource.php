<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Customer;
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
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;



class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Facture';

    protected static ?string $navigationLabel = 'Factures';

    protected static ?string $navigationGroup = 'Ventes';

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
                        Select::make('customer_id')
                            ->label('Client')
                            ->options([
                                null => 'Client passager', // Valeur NULL pour les clients passagers
                                ...Customer::all()->pluck('name', 'id')->toArray()
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (is_null($state)) {
                                    $set('customer_name', 'Client passager');
                                } else {
                                    $customer = Customer::find($state);
                                    if ($customer) {
                                        $set('customer_name', $customer->name);
                                    }
                                }
                            }),

                        // Ajoutez ce champ conditionnel
                        TextInput::make('customer_name')
                            ->label('Nom du client passager')
                            ->required(fn(Get $get): bool => is_null($get('customer_id')))
                            ->visible(fn(Get $get): bool => is_null($get('customer_id'))),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id())
                            ->required(),
                        DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now()),

                        TimePicker::make('time')
                            ->label('Heure')
                            ->required()
                            ->default(now()),

                        Toggle::make('paid')
                            ->label('Payée')
                            ->inline(false),

                        Toggle::make('delivered')
                            ->label('Livrée')
                            ->inline(false),

                        TextInput::make('discount')
                            ->label('Remise')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Articles')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Produit')
                                    ->options(function (Get $get) {
                                        $selectedProducts = collect($get('../../items'))
                                            ->pluck('product_id')
                                            ->filter()
                                            ->toArray();

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
                                            $set('unit_price', $product->selling_price);
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(function (Get $get, $state) {
                                        $product = Product::find($get('product_id'));
                                        return $product ? $product->quantity_in_stock : 0;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateItemSubtotal($get, $set);
                                    })
                                    ->hint(function (Get $get) {
                                        $product = Product::find($get('product_id'));
                                        return $product ? 'Stock disponible: ' . $product->quantity_in_stock : '';
                                    })
                                    ->helperText(function (Get $get, $state) {
                                        $product = Product::find($get('product_id'));
                                        if (!$product)
                                            return '';

                                        if ($state > $product->quantity_in_stock) {
                                            return 'Attention: Quantité supérieure au stock disponible!';
                                        }
                                        return '';
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
                            ->itemLabel(fn(array $state): ?string => Product::find($state['product_id'])?->name ?? null)
                            ->addActionLabel('Ajouter un article')
                            ->minItems(1)
                            ->reorderable()
                            ->cloneable()
                            ->collapsible()
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $productIds = array_column($data['items'] ?? [], 'product_id');
                                if (count($productIds) !== count(array_unique($productIds))) {
                                    throw new \Exception('Un produit ne peut être ajouté qu\'une seule fois');
                                }
                                return $data;
                            }),
                    ]),

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
                    ])->columns(2)
            ]);
    }

    protected static function updateItemSubtotal(Get $get, Set $set): void
    {
        $quantity = $get('quantity');
        $unitPrice = $get('unit_price');

        if ($quantity && $unitPrice) {
            $subtotal = $quantity * $unitPrice;
            $set('subtotal', number_format($subtotal, 2, '.', ''));

            // Update invoice totals
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
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
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
                    ->label('Payée')
                    ->boolean(),

                Tables\Columns\IconColumn::make('delivered')
                    ->label('Livrée')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name'),

                Tables\Filters\Filter::make('paid')
                    ->query(fn(Builder $query): Builder => $query->where('paid', true))
                    ->label('Payées uniquement'),

                Tables\Filters\Filter::make('delivered')
                    ->query(fn(Builder $query): Builder => $query->where('delivered', true))
                    ->label('Livrées uniquement'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn(Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),
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