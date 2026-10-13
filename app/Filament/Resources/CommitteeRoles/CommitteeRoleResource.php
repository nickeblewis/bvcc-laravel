<?php

namespace App\Filament\Resources\CommitteeRoles;

use App\Enums\Area;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\CommitteeRoles\Pages\ManageCommitteeRoles;
use App\Models\CommitteeRole;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CommitteeRoleResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = CommitteeRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Committee';

    protected static ?int $navigationSort = 6;

    public static function area(): Area
    {
        return Area::Content;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('email')->label('Role email')->email()->maxLength(255),
            TextInput::make('holder_name')
                ->label('Office holder')
                ->helperText('Shown on the committee page. Leave empty if the post is vacant or the person prefers not to be named.')
                ->maxLength(255),
            Textarea::make('description')->rows(2)->maxLength(500),
            Toggle::make('is_listed')->label('Show on the committee page')->default(true),
            Toggle::make('takes_enquiries')
                ->label('Offer as a topic on the contact form')
                ->helperText('Enquiries on this topic are emailed to the role email.'),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('title')->searchable()->weight('medium'),
                TextColumn::make('holder_name')->label('Office holder')->placeholder('Vacant or not named'),
                TextColumn::make('email')->color('gray'),
                IconColumn::make('is_listed')->label('Listed')->boolean(),
                IconColumn::make('takes_enquiries')->label('Contact form')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCommitteeRoles::route('/')];
    }
}
