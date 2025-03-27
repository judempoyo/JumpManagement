<?php

namespace App\Filament\Resources\FinancialEntryResource\Pages;

use App\Filament\Resources\FinancialEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialEntry extends EditRecord
{
    protected static string $resource = FinancialEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
