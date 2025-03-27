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

class FinancialEntryResource extends Resource
{
    protected static ?string $model = FinancialEntry::class;
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('remaining_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\Toggle::make('is_paid')
                    ->required(),
                Forms\Components\TextInput::make('source_document_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('source_document_type')
                    ->required(),
                Forms\Components\TextInput::make('partner_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('partner_type')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'debt' => 'danger',
                        'receivable' => 'success',
                    }),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Partenaire'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->money('USD')
                    ->sortable(),
                    Tables\Columns\IconColumn::make('is_paid')
                    ->boolean(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'debt' => 'Dettes',
                        'receivable' => 'Créances',
                    ]),
                Tables\Filters\Filter::make('is_paid')
                    ->query(fn (Builder $query): Builder => $query->where('is_paid', false))
                    ->label('Unpaid only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('register_payment')
                    ->icon('heroicon-o-banknotes')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->default(now()),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Espèces',
                                'check' => 'Chèque',
                                'transfer' => 'Virement',
                                'card' => 'Carte bancaire',
                            ]),
                    ])
                    ->action(function (FinancialEntry $record, array $data): void {
                        $record->payments()->create([
                            'amount' => $data['amount'],
                            'payment_date' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'user_id' => auth()->id(),
                        ]);
                        $record->updateBalance();
                    }),
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
            'index' => Pages\ListFinancialEntries::route('/'),
            'create' => Pages\CreateFinancialEntry::route('/create'),
            'view' => Pages\ViewFinancialEntry::route('/{record}'),
            'edit' => Pages\EditFinancialEntry::route('/{record}/edit'),
        ];
    }
}
