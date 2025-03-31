<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinancialEntryResource\Pages;
use App\Filament\Resources\FinancialEntryResource\RelationManagers;
use App\Models\FinancialEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class FinancialEntryResource extends Resource
{
    protected static ?string $model = FinancialEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $modelLabel = 'Entrée financière';

    protected static ?string $navigationLabel = 'Entrées financières';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'receivable' => 'Créance',
                                'debt' => 'Dette',
                            ])
                            ->live(),
                            
                        Forms\Components\Select::make('source_document_type')
                            ->label('Document source')
                            ->required()
                            ->options([
                                'App\Models\Invoice' => 'Facture',
                                'App\Models\PurchaseOrder' => 'Bon de commande',
                            ]),
                            
                        Forms\Components\Select::make('source_document_id')
                            ->label('Référence document')
                            ->searchable()
                            ->required(),
                            
                        Forms\Components\Select::make('partner_type')
                            ->label('Type partenaire')
                            ->options([
                                'App\Models\Customer' => 'Client',
                                'App\Models\Supplier' => 'Fournisseur',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('partner_id')
                            ->label('Partenaire')
                            ->searchable()
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Montants et dates')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Montant total')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                            
                        Forms\Components\TextInput::make('remaining_amount')
                            ->label('Montant restant')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                            
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Date de création')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Date d\'échéance'),
                            
                        Forms\Components\Toggle::make('is_paid')
                            ->label('Payé'),
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'receivable' => 'success',
                        'debt' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'receivable' => 'Créance',
                        'debt' => 'Dette',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('sourceDocument.id')
                    ->label('Document')
                    ->formatStateUsing(fn ($state, $record) => $record->source_document_type::find($state)?->id),
                    
                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Partenaire'),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Montant total')
                    ->money('USD'),
                    
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Reste à payer')
                    ->money('USD')
                    ->color(fn ($record) => $record->remaining_amount > 0 ? 'danger' : 'success'),
                    
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Statut')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date()
                    ->color(fn ($record) => $record->due_date && $record->due_date < now() ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'receivable' => 'Créance',
                        'debt' => 'Dette',
                    ]),
                    
                Tables\Filters\Filter::make('is_paid')
                    ->label('Payé seulement')
                    ->query(fn (Builder $query): Builder => $query->where('is_paid', true)),
                    
                Tables\Filters\Filter::make('unpaid')
                    ->label('Impayé seulement')
                    ->query(fn (Builder $query): Builder => $query->where('is_paid', false)),
                    
                Tables\Filters\Filter::make('overdue')
                    ->label('En retard')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\Action::make('add_payment')
                    ->label('Ajouter paiement')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant')
                            ->required()
                            ->numeric()
                            ->maxValue(fn ($record) => $record->remaining_amount),
                            
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Date de paiement')
                            ->default(now()),
                            
                        Forms\Components\Select::make('payment_method')
                            ->label('Méthode de paiement')
                            ->options([
                                'cash' => 'Espèces',
                                'check' => 'Chèque',
                                'transfer' => 'Virement',
                                'card' => 'Carte bancaire',
                            ]),
                            
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes'),
                    ])
                    ->action(function (FinancialEntry $record, array $data): void {
                        $record->payments()->create([
                            'amount' => $data['amount'],
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'reference' => $data['reference'],
                            'notes' => $data['notes'],
                            'user_id' => auth()->id(),
                        ]);
                        
                        $record->updateBalance();
                    }),
                    
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
            'index' => Pages\ListFinancialEntries::route('/'),
            'create' => Pages\CreateFinancialEntry::route('/create'),
            'view' => Pages\ViewFinancialEntry::route('/{record}'),
            'edit' => Pages\EditFinancialEntry::route('/{record}/edit'),
        ];
    }
}
