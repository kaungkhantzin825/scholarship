<?php

namespace App\Filament\Resources\AdEventResource\Pages;

use App\Filament\Resources\AdEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdEvents extends ListRecords
{
    protected static string $resource = AdEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
