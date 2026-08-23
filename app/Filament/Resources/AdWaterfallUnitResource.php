<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdWaterfallUnitResource\Pages;
use App\Models\AdWaterfallUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdWaterfallUnitResource extends Resource
{
    protected static ?string $model = AdWaterfallUnit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Ad Waterfall';
    protected static ?string $modelLabel = 'waterfall ad unit';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('platform')
                    ->options(['android' => 'Android', 'ios' => 'iOS'])
                    ->required(),
                Forms\Components\Select::make('ad_type')
                    ->options(['banner' => 'Banner', 'interstitial' => 'Interstitial'])
                    ->required(),
                Forms\Components\TextInput::make('ad_unit_id')
                    ->label('Ad Unit ID')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('priority')
                    ->helperText('Tried in ascending order — 0 goes first, then 1, 2, ... The app falls through to the next tier only if the previous one fails to load.')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('is_enabled')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority')
            ->reorderable('priority')
            ->columns([
                Tables\Columns\TextColumn::make('platform')->badge(),
                Tables\Columns\TextColumn::make('ad_type')->badge()->color('primary'),
                Tables\Columns\TextColumn::make('ad_unit_id')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('priority')->sortable(),
                Tables\Columns\ToggleColumn::make('is_enabled'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options(['android' => 'Android', 'ios' => 'iOS']),
                Tables\Filters\SelectFilter::make('ad_type')
                    ->options(['banner' => 'Banner', 'interstitial' => 'Interstitial']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdWaterfallUnits::route('/'),
            'create' => Pages\CreateAdWaterfallUnit::route('/create'),
            'edit' => Pages\EditAdWaterfallUnit::route('/{record}/edit'),
        ];
    }
}
