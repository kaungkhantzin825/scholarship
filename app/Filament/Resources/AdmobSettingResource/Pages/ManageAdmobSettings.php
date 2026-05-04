<?php

namespace App\Filament\Resources\AdmobSettingResource\Pages;

use App\Filament\Resources\AdmobSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ManageAdmobSettings extends ListRecords
{
    protected static string $resource = AdmobSettingResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Ensure at least one settings record exists
        if (\App\Models\AdmobSetting::count() === 0) {
            \App\Models\AdmobSetting::create([
                'is_enabled' => true,
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // No create action - we only want one settings record
        ];
    }
}
