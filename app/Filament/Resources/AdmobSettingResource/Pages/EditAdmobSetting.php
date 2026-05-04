<?php

namespace App\Filament\Resources\AdmobSettingResource\Pages;

use App\Filament\Resources\AdmobSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdmobSetting extends EditRecord
{
    protected static string $resource = AdmobSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
