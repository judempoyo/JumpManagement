<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'Produit';

    protected static ?string $navigationLabel = 'Produits';

    protected static ?string $navigationGroup = 'Gestion des stocks';

    protected static ?int $navigationSort = 2;

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
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du produit')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('code')
                            ->label('Code produit')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Select::make('category_id')
                            ->label('Catégorie')
                            ->required()
                            ->options(Category::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('unit_id')
                            ->label('Unité')
                            ->required()
                            ->options(Unit::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])->columns(3),
                
                Section::make('Prix et stock')
                    ->schema([
                        Forms\Components\TextInput::make('selling_price')
                            ->label('Prix de vente')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        
                        Forms\Components\TextInput::make('purchase_cost')
                            ->label('Coût d\'achat')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Prix de revient')
                            ->numeric()
                            ->prefix('$'),
                        
                        Forms\Components\TextInput::make('quantity_in_stock')
                            ->label('Quantité en stock')
                            ->required()
                            ->numeric()
                            ->default(0),
                        
                        Forms\Components\TextInput::make('alert_quantity')
                            ->label('Seuil d\'alerte')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
                
                Section::make('Image et description')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Image du produit')
                            ->image()
                            ->directory('products')
                            ->columnSpan(1),
                        
                            Forms\Components\MarkdownEditor::make('description')
                            ->label('Description')
                            ->columnSpan(2),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->circular(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn (Product $record) => $record->quantity_in_stock <= $record->alert_quantity ? 'danger' : 'success')
                    ->description(fn (Product $record) => $record->quantity_in_stock <= $record->alert_quantity ? 'Stock faible' : null),
                
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Prix de vente')
                    ->money('USD')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('purchase_cost')
                    ->label('Coût d\'achat')
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Catégorie'),
                
                Tables\Filters\SelectFilter::make('unit')
                    ->relationship('unit', 'name')
                    ->label('Unité'),
                
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock faible')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('quantity_in_stock', '<=', 'alert_quantity')),
            ])
            ->actions([
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
