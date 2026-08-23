<?php

namespace App\Filament\Resources\AdWaterfallUnitResource\Pages;

use App\Filament\Resources\AdWaterfallUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdWaterfallUnits extends ListRecords
{
    protected static string $resource = AdWaterfallUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
