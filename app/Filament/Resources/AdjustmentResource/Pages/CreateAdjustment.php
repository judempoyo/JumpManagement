<?php

namespace App\Filament\Resources\AdjustmentResource\Pages;

use App\Filament\Resources\AdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdjustment extends CreateRecord
{
    protected static string $resource = AdjustmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
