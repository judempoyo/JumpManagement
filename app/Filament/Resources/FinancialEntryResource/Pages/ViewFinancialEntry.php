<?php

namespace App\Filament\Resources\FinancialEntryResource\Pages;

use App\Filament\Resources\FinancialEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFinancialEntry extends ViewRecord
{
    protected static string $resource = FinancialEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
