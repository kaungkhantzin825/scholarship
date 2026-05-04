<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScholarshipResource\Pages;
use App\Models\Scholarship;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

class ScholarshipResource extends Resource
{
    protected static ?string $model = Scholarship::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Scholarships';
    protected static ?int    $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'active'  => 'Active',
                            'expired' => 'Expired',
                            'closed'  => 'Closed',
                        ])
                        ->required()
                        ->default('active'),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured Scholarship'),

                    Forms\Components\Textarea::make('excerpt')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Banner Image')
                ->schema([
                    Forms\Components\FileUpload::make('banner_image')
                        ->image()
                        ->directory('scholarships/banners')
                        ->disk('public')
                        ->visibility('public')
                        ->maxSize(5120)
                        ->label('Banner Image (recommended: 1200×630)')
                        ->helperText('Select an image. It will be saved when you click the Save button below.')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->downloadable()
                        ->openable()
                        ->deletable()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->imageResizeTargetWidth('1200')
                        ->imageResizeTargetHeight('630')
                        ->storeFiles(false),
                ]),

            Forms\Components\Section::make('Scholarship Details')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('level')
                        ->options([
                            'any'      => 'Any Level',
                            'diploma'  => 'Diploma',
                            'bachelor' => 'Bachelor',
                            'master'   => 'Master',
                            'phd'      => 'PhD',
                        ])
                        ->required()
                        ->default('any'),

                    Forms\Components\TextInput::make('field_of_study')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('country')
                        ->maxLength(100)
                        ->label('Host Country'),

                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('$')
                        ->label('Scholarship Amount'),

                    Forms\Components\Select::make('amount_type')
                        ->options([
                            'full'    => 'Full Scholarship',
                            'partial' => 'Partial Scholarship',
                            'monthly' => 'Monthly Stipend',
                            'other'   => 'Other',
                        ])
                        ->default('full'),

                    Forms\Components\TextInput::make('currency')
                        ->maxLength(10)
                        ->default('USD'),

                    Forms\Components\DatePicker::make('deadline')
                        ->label('Application Deadline'),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Program Start Date'),

                    Forms\Components\TextInput::make('official_link')
                        ->url()
                        ->maxLength(500)
                        ->label('Official Website')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Rich Content')
                ->schema([
                    Forms\Components\RichEditor::make('description')
                        ->required()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('scholarship-attachments')
                        ->label('Full Description'),

                    Forms\Components\RichEditor::make('eligibility')
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('scholarship-attachments')
                        ->label('Eligibility Criteria'),

                    Forms\Components\Textarea::make('benefits')
                        ->rows(4)
                        ->label('Benefits & Coverage'),

                    Forms\Components\Textarea::make('required_documents')
                        ->rows(4)
                        ->label('Required Documents'),
                ]),

            Forms\Components\Section::make('Tags')
                ->schema([
                    Forms\Components\Select::make('tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->required(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('banner_image')
                    ->disk('public')
                    ->width(80)
                    ->height(50)
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('country')
                    ->searchable(),

                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'phd'      => 'danger',
                        'master'   => 'warning',
                        'bachelor' => 'info',
                        'diploma'  => 'gray',
                        default    => 'success',
                    }),

                Tables\Columns\TextColumn::make('deadline')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->is_expired ? 'danger' : 'success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger'  => 'expired',
                        'gray'    => 'closed',
                    ]),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),

                Tables\Columns\TextColumn::make('applications_count')
                    ->numeric()
                    ->sortable()
                    ->label('Applications'),

                Tables\Columns\TextColumn::make('views_count')
                    ->numeric()
                    ->sortable()
                    ->label('Views'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'  => 'Active',
                        'expired' => 'Expired',
                        'closed'  => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'any'      => 'Any',
                        'diploma'  => 'Diploma',
                        'bachelor' => 'Bachelor',
                        'master'   => 'Master',
                        'phd'      => 'PhD',
                    ]),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScholarships::route('/'),
            'create' => Pages\CreateScholarship::route('/create'),
            'edit' => Pages\EditScholarship::route('/{record}/edit'),
            'ai-generator' => Pages\AiScholarshipGenerator::route('/ai-generator'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
