<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdEventResource\Pages;
use App\Models\AdEvent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdEventResource extends Resource
{
    protected static ?string $model = AdEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Ad Events';
    protected static ?string $modelLabel = 'ad event';

    // Read-only log — events arrive from the app, nothing to create/edit here.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ad_type')->badge(),
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (string $state) => $state === 'click' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('platform')->toggleable(),
                Tables\Columns\TextColumn::make('screen')->toggleable(),
                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device')
                    ->limit(12)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_suspicious')
                    ->label('Flagged')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('suspicious_reason')
                    ->label('Reason')
                    ->badge()
                    ->color('danger')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_suspicious')
                    ->label('Suspicious only'),
                Tables\Filters\SelectFilter::make('ad_type')
                    ->options(['banner' => 'Banner', 'interstitial' => 'Interstitial']),
                Tables\Filters\SelectFilter::make('event_type')
                    ->options(['impression' => 'Impression', 'click' => 'Click']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListAdEvents::route('/'),
            'view' => Pages\ViewAdEvent::route('/{record}'),
        ];
    }
}
