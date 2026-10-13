<?php

namespace App\Filament\Resources\Articles;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Support\ContentFields;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ArticleResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'News';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function area(): Area
    {
        return Area::Content;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                ContentFields::title()->columnSpanFull(),
                ContentFields::slug('/news/'),
                Select::make('article_category_id')->label('Category')->relationship('category', 'name'),
                Textarea::make('excerpt')
                    ->label('Summary')
                    ->helperText('One or two sentences. Shown in the news list and used as the search description.')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),
                MarkdownEditor::make('body')->columnSpanFull(),
                DateTimePicker::make('published_at')
                    ->label('Publish on')
                    ->seconds(false)
                    ->helperText('Leave empty to keep it as a draft.'),
                TextInput::make('author_name')->label('Author')->maxLength(255),
            ]),
            ContentFields::photo('news'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->searchable()->wrap()->weight('medium'),
                TextColumn::make('category.name')->badge()->color('gray'),
                TextColumn::make('published_at')->label('Published')->date('j M Y')->placeholder('Draft')->sortable(),
            ])
            ->filters([SelectFilter::make('article_category_id')->label('Category')->relationship('category', 'name')])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
