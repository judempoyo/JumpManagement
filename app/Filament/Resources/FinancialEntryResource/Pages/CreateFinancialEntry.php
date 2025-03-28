<?php

namespace App\Filament\Resources\FinancialEntryResource\Pages;

use App\Filament\Resources\FinancialEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialEntry extends CreateRecord
{
    protected static string $resource = FinancialEntryResource::class;
}
