<?php

namespace App\Filament\Resources\AdWaterfallUnitResource\Pages;

use App\Filament\Resources\AdWaterfallUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdWaterfallUnit extends EditRecord
{
    protected static string $resource = AdWaterfallUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
