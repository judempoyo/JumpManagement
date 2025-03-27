<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $modelLabel = 'Dépense';

    protected static ?string $navigationLabel = 'Dépenses';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de base')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                            
                        Forms\Components\Select::make('invoice_id')
                            ->label('Facture associée')
                            ->relationship('invoice', 'id')
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\Select::make('user_id')
                            ->label('Responsable')
                            ->relationship('user', 'name')
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Coordonnées')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(20),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->columnSpanFull(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Détails supplémentaires')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'office' => 'Bureau',
                                'travel' => 'Voyage',
                                'equipment' => 'Équipement',
                                'other' => 'Autre',
                            ]),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'office' => 'info',
                        'travel' => 'warning',
                        'equipment' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'office' => 'Bureau',
                        'travel' => 'Voyage',
                        'equipment' => 'Équipement',
                        'other' => 'Autre',
                    ]),
                    
                Tables\Filters\SelectFilter::make('user')
                    ->label('Responsable')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'view' => Pages\ViewExpense::route('/{record}'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
