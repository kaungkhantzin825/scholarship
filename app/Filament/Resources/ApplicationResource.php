<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\Application;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Applications';
    protected static ?int    $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()->disabled(),

                Forms\Components\Select::make('scholarship_id')
                    ->relationship('scholarship', 'title')
                    ->searchable()->disabled(),

                Forms\Components\Select::make('status')
                    ->options(Application::STATUSES)
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Admin Notes')
                    ->rows(4)->columnSpanFull(),

                Forms\Components\Textarea::make('cover_letter')
                    ->rows(6)->columnSpanFull()->disabled(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()->sortable()->label('Applicant'),

                Tables\Columns\TextColumn::make('user.email')
                    ->searchable()->label('Email'),

                Tables\Columns\TextColumn::make('scholarship.title')
                    ->searchable()->limit(40)->label('Scholarship'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'reviewed',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ]),

                Tables\Columns\TextColumn::make('applied_at')
                    ->dateTime()->sortable()->label('Applied'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Application::STATUSES),

                Tables\Filters\SelectFilter::make('scholarship')
                    ->relationship('scholarship', 'title'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Application $record) => $record->status !== 'approved')
                    ->action(fn (Application $record) => $record->update(['status' => 'approved'])),

                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Application $record) => $record->status !== 'rejected')
                    ->action(fn (Application $record) => $record->update(['status' => 'rejected'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_all')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['status' => 'approved'])),
                ]),
            ])
            ->defaultSort('applied_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'edit'  => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
