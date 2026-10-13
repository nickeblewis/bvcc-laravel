<?php

namespace App\Filament\Resources\Faqs;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?int $navigationSort = 5;

    public static function area(): Area
    {
        return Area::Content;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')
                ->helperText('Write it the way someone would ask it: "How much does a session cost?"')
                ->required()
                ->maxLength(255),
            Textarea::make('answer')
                ->helperText('Answer in the first sentence, then add detail. Two to four sentences is ideal.')
                ->required()
                ->rows(5),
            Select::make('category')->options(Faq::CATEGORIES)->required(),
            Toggle::make('is_published')->label('Published')->default(true),
            Toggle::make('to_confirm')->label('Needs checking with the committee'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('question')->searchable()->wrap()->weight('medium'),
                TextColumn::make('category')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => Faq::CATEGORIES[$state] ?? $state),
                IconColumn::make('to_confirm')->label('To check')->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)->trueColor('warning')->falseIcon(null),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([SelectFilter::make('category')->options(Faq::CATEGORIES)])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFaqs::route('/')];
    }
}
