<?php

namespace App\Filament\Resources\ArticleCategories;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\ArticleCategories\Pages\ManageArticleCategories;
use App\Filament\Support\ContentFields;
use App\Models\ArticleCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ArticleCategoryResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = ArticleCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'News categories';

    protected static ?int $navigationSort = 7;

    public static function area(): Area
    {
        return Area::Content;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ContentFields::title('name'),
            ContentFields::slug('/news?category='),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('medium'),
                TextColumn::make('articles_count')->label('Posts')->counts('articles'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageArticleCategories::route('/')];
    }
}
