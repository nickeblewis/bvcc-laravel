<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Enums\Area;
use App\Filament\Support\MembershipTable;
use App\Filament\Support\Staff;
use App\Models\Member;
use App\Models\MembershipTier;
use App\Services\Memberships;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['lead', 'tier', 'payments'])->withCount('members'))
            ->defaultSort('expires_on', 'desc')
            ->columns(MembershipTable::columns(withMember: false))
            ->headerActions([
                Action::make('addMembership')
                    ->label('Add membership')
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => Staff::can(Area::Memberships))
                    ->modalWidth('lg')
                    ->schema([
                        Select::make('membership_tier_id')
                            ->label('Tier')
                            ->options(fn (): array => MembershipTier::where('on_sale', true)->orderBy('sort')->pluck('name', 'id')->all())
                            ->required(),
                        DatePicker::make('starts_on')->default(today())->required(),
                        Select::make('covered')
                            ->label('Also covers (family tiers)')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Member::query()->search($search)->limit(20)->get()->pluck('name', 'id')->all())
                            ->getOptionLabelsUsing(fn (array $values): array => Member::findMany($values)->pluck('name', 'id')->all()),
                    ])
                    ->action(function (array $data): void {
                        /** @var Member $member */
                        $member = $this->getOwnerRecord();

                        app(Memberships::class)->create(
                            $member,
                            MembershipTier::query()->whereKey($data['membership_tier_id'])->firstOrFail(),
                            Carbon::parse($data['starts_on']),
                            array_values(array_map(intval(...), $data['covered'] ?? [])),
                        );

                        Notification::make()->success()->title('Membership added')->send();
                    }),
            ])
            ->recordActions([ActionGroup::make(MembershipTable::actions())]);
    }
}
