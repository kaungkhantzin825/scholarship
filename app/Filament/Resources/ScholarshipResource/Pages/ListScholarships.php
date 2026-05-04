<?php

namespace App\Filament\Resources\ScholarshipResource\Pages;

use App\Filament\Resources\ScholarshipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Category;

class ListScholarships extends ListRecords
{
    protected static string $resource = ScholarshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_ai')
                ->label('Generate with AI')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->url(fn (): string => \App\Filament\Resources\ScholarshipResource::getUrl('ai-generator')),
            Actions\CreateAction::make(),
        ];
    }
    // Action replaced with URL redirect.
}
