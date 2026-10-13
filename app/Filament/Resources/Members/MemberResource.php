<?php

namespace App\Filament\Resources\Members;

use App\Enums\Area;
use App\Enums\MembershipStatus;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Pages\EditMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\RelationManagers\MembershipsRelationManager;
use App\Filament\Support\Staff;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipTier;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MemberResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Club';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function area(): Area
    {
        return Area::Members;
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Person')->columns(2)->schema([
                TextInput::make('first_name')->required()->maxLength(255),
                TextInput::make('last_name')->required()->maxLength(255),
                DatePicker::make('date_of_birth')->maxDate(today()),
                TextInput::make('email')->email()->maxLength(255),
                TextInput::make('phone')->tel()->maxLength(255),
            ]),
            Section::make('Address')->columns(2)->collapsed()->schema([
                TextInput::make('address_line_1')->maxLength(255),
                TextInput::make('address_line_2')->maxLength(255),
                TextInput::make('town')->maxLength(255),
                TextInput::make('postcode')->maxLength(20),
            ]),
            Section::make('Paddling')->columns(2)->schema([
                TextInput::make('paddle_uk_number')->label('Paddle UK number')->maxLength(255),
                DatePicker::make('paddle_uk_expires_on')->label('Paddle UK membership expires'),
                Textarea::make('experience')->label('Experience and qualifications')->rows(3)->columnSpanFull(),
                Toggle::make('photo_consent')->label('Consents to photos and video'),
            ]),
            // Hidden fields are not saved, so staff without access cannot
            // overwrite what they cannot see.
            Section::make('Emergency contacts and medical information')
                ->description('Stored encrypted. Only welfare staff and administrators can see this.')
                ->icon(Heroicon::OutlinedLockClosed)
                ->visible(fn (): bool => Staff::can(Area::Medical))
                ->schema([
                    Repeater::make('emergency_contacts')
                        ->schema([
                            TextInput::make('name')->required(),
                            TextInput::make('relationship'),
                            TextInput::make('phone')->tel()->required(),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->maxItems(3)
                        ->addActionLabel('Add a contact'),
                    Textarea::make('medical_notes')
                        ->label('Medical conditions, disabilities and medication')
                        ->rows(3),
                ]),
            Section::make('Notes')->collapsed()->schema([
                Textarea::make('notes')->hiddenLabel()->rows(3),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['memberships' => fn (QueryBuilder $q) => Membership::whereCurrent($q), 'memberships.tier']))
            ->defaultSort('last_name')
            ->columns([
                TextColumn::make('last_name')
                    ->label('Name')
                    ->formatStateUsing(fn (Member $record): string => $record->name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('membership')
                    ->state(fn (Member $record): string => $record->memberships->first()->tier->name ?? 'No current membership')
                    ->badge()
                    ->color(fn (Member $record): string => $record->memberships->isEmpty() ? 'gray' : 'success'),
                TextColumn::make('expires')
                    ->state(fn (Member $record) => $record->memberships->first()?->expires_on)
                    ->date('j M Y')
                    ->color(fn (Member $record): ?string => $record->memberships->first()?->expiresWithin(30) ? 'warning' : null),
                TextColumn::make('date_of_birth')
                    ->label('Age')
                    ->formatStateUsing(fn (Member $record): string => (string) $record->age())
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('phone')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('town')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tier')
                    ->label('Membership tier')
                    ->options(fn (): array => MembershipTier::orderBy('sort')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $tier) => $q->whereHas('memberships', function (Builder $m) use ($tier): void {
                            Membership::whereCurrent($m);
                            $m->where('membership_tier_id', $tier);
                        }),
                    )),
                Filter::make('expiring')
                    ->label('Expiring in the next 30 days')
                    ->query(fn (Builder $query) => $query->whereHas('memberships', fn (Builder $m) => Membership::whereExpiringWithin($m, 30))),
                Filter::make('lapsed')
                    ->label('No current membership')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('memberships', fn (Builder $m) => Membership::whereCurrent($m))),
                Filter::make('unpaid')
                    ->label('Membership awaiting payment')
                    ->query(fn (Builder $query) => $query->whereHas('memberships', fn (Builder $m) => $m->where('status', MembershipStatus::Pending))),
                SelectFilter::make('age')
                    ->label('Age group')
                    ->options(['junior' => 'Under 18', 'adult' => '18 and over'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'junior' => $query->whereDate('date_of_birth', '>', today()->subYears(18)),
                        'adult' => $query->whereDate('date_of_birth', '<=', today()->subYears(18)),
                        default => $query,
                    }),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [MembershipsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}
