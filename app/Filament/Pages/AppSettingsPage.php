<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use App\Models\AppVersion;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;

class AppSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $title = 'App Settings & Force Update';
    protected static string $view = 'filament.pages.app-settings-page';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = AppVersion::firstOrCreate(['id' => 1]);
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Android App Update Settings')
                    ->columns(2)
                    ->schema([
                        TextInput::make('android_latest_version')
                            ->label('Latest Version')
                            ->required(),
                        TextInput::make('android_required_version')
                            ->label('Minimum Required Version')
                            ->required()
                            ->helperText('If user version is below this, they are forced to update.'),
                        TextInput::make('android_store_url')
                            ->label('Play Store URL')
                            ->url()
                            ->columnSpanFull(),
                    ]),
                Section::make('iOS App Update Settings')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ios_latest_version')
                            ->label('Latest Version')
                            ->required(),
                        TextInput::make('ios_required_version')
                            ->label('Minimum Required Version')
                            ->required()
                            ->helperText('If user version is below this, they are forced to update.'),
                        TextInput::make('ios_store_url')
                            ->label('App Store URL')
                            ->url()
                            ->columnSpanFull(),
                    ]),
                Section::make('General Settings')
                    ->schema([
                        Textarea::make('force_update_message')
                            ->label('Force Update Message')
                            ->helperText('Message shown to users when forced to update.'),
                        Toggle::make('is_maintenance_mode')
                            ->label('Maintenance Mode')
                            ->helperText('Turn on to block all users from accessing the app.'),
                        Textarea::make('maintenance_message')
                            ->label('Maintenance Message'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $settings = AppVersion::firstOrCreate(['id' => 1]);
        $settings->update($this->form->getState());

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
