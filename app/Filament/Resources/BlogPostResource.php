<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int    $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Post Details')->columns(2)->schema([
                Forms\Components\TextInput::make('title')
                    ->required()->maxLength(255)->columnSpanFull(),

                Forms\Components\Select::make('post_category')
                    ->options([
                        'news'          => '📰 News',
                        'tips'          => '💡 Tips & Guides',
                        'success-story' => '🏆 Success Stories',
                        'guide'         => '📖 How-To Guide',
                    ]),

                Forms\Components\TextInput::make('author_name')
                    ->maxLength(100),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Publish Date'),

                Forms\Components\Toggle::make('is_featured')->label('Featured'),

                Forms\Components\Textarea::make('excerpt')
                    ->rows(3)->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Cover Image')->schema([
                    Forms\Components\FileUpload::make('cover_image')
                        ->image()->imageEditor()
                        ->directory('blog/covers')
                        ->disk('public')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120),
                ]),

            Forms\Components\Section::make('Content')->schema([
                Forms\Components\RichEditor::make('content')
                    ->required()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('blog-attachments')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->disk('public')
                    ->width(70)->height(45),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()->sortable()->limit(50),

                Tables\Columns\BadgeColumn::make('post_category')
                    ->label('Category'),

                Tables\Columns\TextColumn::make('author_name')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_featured')->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->numeric()->sortable()->label('Views'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('post_category')
                    ->options([
                        'news'          => 'News',
                        'tips'          => 'Tips',
                        'success-story' => 'Success Stories',
                        'guide'         => 'Guides',
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured'),
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
                ]),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit'   => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
