<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;



class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $modelLabel = 'Paiement';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de base')
                    ->schema([
                        Forms\Components\Select::make('financial_entry_id')
                            ->label('Entrée financière')
                            ->relationship('financialEntry', 'id')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                            
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Date de paiement')
                            ->required()
                            ->default(now()),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Détails du paiement')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->label('Méthode de paiement')
                            ->required()
                            ->options([
                                'cash' => 'Espèces',
                                'check' => 'Chèque',
                                'transfer' => 'Virement',
                                'card' => 'Carte bancaire',
                                'other' => 'Autre',
                            ]),
                            
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('user_id')
                            ->label('Enregistré par')
                            ->relationship('user', 'name')
                            ->default(auth()->id())
                            ->required(),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('financialEntry.sourceDocument.id')
                    ->label('Document')
                    ->formatStateUsing(fn ($state, $record) => $record->financialEntry->source_document_type::find($record->financialEntry->source_document_id)?->id),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Espèces',
                        'check' => 'Chèque',
                        'transfer' => 'Virement',
                        'card' => 'Carte',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Enregistré par'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Méthode de paiement')
                    ->options([
                        'cash' => 'Espèces',
                        'check' => 'Chèque',
                        'transfer' => 'Virement',
                        'card' => 'Carte bancaire',
                    ]),
                    
                Tables\Filters\SelectFilter::make('user')
                    ->label('Enregistré par')
                    ->relationship('user', 'name'),
                    
                Tables\Filters\Filter::make('payment_date')
                    ->label('Date de paiement')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
