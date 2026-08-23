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
                Forms\Components\Section::make('Policy & Content Settings')
                    ->description('Required by AdMob policy so ads are tagged correctly for the audience viewing them.')
                    ->schema([
                        Forms\Components\Select::make('max_ad_content_rating')
                            ->label('Max Ad Content Rating')
                            ->options([
                                'G' => 'G — General audiences',
                                'PG' => 'PG — Parental guidance',
                                'T' => 'T — Teen',
                                'MA' => 'MA — Mature audiences',
                            ])
                            ->default('T')
                            ->required(),
                        Forms\Components\Toggle::make('tag_for_child_directed_treatment')
                            ->label('Treat app as child-directed (COPPA)')
                            ->helperText('Only enable if this app is directed at children under 13.'),
                        Forms\Components\Toggle::make('tag_for_under_age_of_consent')
                            ->label('Tag users as under age of consent (GDPR)')
                            ->helperText('Leave off unless your audience is known to be under the age of consent.'),
                        Forms\Components\Textarea::make('test_device_ids')
                            ->label('Test Device IDs')
                            ->helperText('Comma-separated AdMob test device IDs. Devs/QA devices listed here always get test ads, so nobody accidentally clicks a live ad.')
                            ->rows(2),
                    ]),
                Forms\Components\Section::make('Ad Frequency (Interstitials)')
                    ->description('Keeps interstitials from feeling excessive — capped quietly in the app, with no limit message shown to the user.')
                    ->schema([
                        Forms\Components\TextInput::make('interstitial_min_interval_seconds')
                            ->label('Minimum Seconds Between Interstitials')
                            ->numeric()
                            ->minValue(30)
                            ->default(90)
                            ->required(),
                        Forms\Components\TextInput::make('interstitial_max_per_session')
                            ->label('Max Interstitials Per Session')
                            ->numeric()
                            ->minValue(1)
                            ->default(3)
                            ->required(),
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
