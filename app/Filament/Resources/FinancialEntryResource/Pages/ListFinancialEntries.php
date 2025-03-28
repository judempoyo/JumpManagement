<?php

namespace App\Filament\Resources\FinancialEntryResource\Pages;

use App\Filament\Resources\FinancialEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialEntries extends ListRecords
{
    protected static string $resource = FinancialEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
