<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdmobSettingResource\Pages;
use App\Models\AdmobSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdmobSettingResource extends Resource
{
    protected static ?string $model = AdmobSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'AdMob Settings';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Enable Ads in App')
                            ->default(true),
                    ]),
                Forms\Components\Section::make('Android Credentials')
                    ->schema([
                        Forms\Components\TextInput::make('android_app_id')
                            ->label('Android App ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('android_banner_id')
                            ->label('Android Banner Unit ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('android_interstitial_id')
                            ->label('Android Interstitial Unit ID')
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('iOS Credentials')
                    ->schema([
                        Forms\Components\TextInput::make('ios_app_id')
                            ->label('iOS App ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ios_banner_id')
                            ->label('iOS Banner Unit ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ios_interstitial_id')
                            ->label('iOS Interstitial Unit ID')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label('Ads Enabled'),
                Tables\Columns\TextColumn::make('android_app_id')
                    ->label('Android App ID')
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAdmobSettings::route('/'),
            'edit' => Pages\EditAdmobSetting::route('/{record}/edit'),
        ];
    }
}
