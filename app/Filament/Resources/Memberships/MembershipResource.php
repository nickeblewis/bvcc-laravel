<?php

namespace App\Filament\Resources\Memberships;

use App\Enums\Area;
use App\Enums\MembershipStatus;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Memberships\Pages\ManageMemberships;
use App\Filament\Support\MembershipTable;
use App\Models\Membership;
use App\Models\MembershipTier;
use App\Services\Memberships;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class MembershipResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Membership::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Club';

    protected static ?int $navigationSort = 2;

    public static function area(): Area
    {
        return Area::Memberships;
    }

    public static function getNavigationBadge(): ?string
    {
        $unpaid = Membership::where('status', MembershipStatus::Pending)->count();

        return $unpaid > 0 ? (string) $unpaid : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Awaiting payment';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['lead', 'tier', 'payments'])->withCount('members'))
            ->defaultSort('expires_on')
            ->columns(MembershipTable::columns())
            ->recordUrl(fn (Membership $record): string => MemberResource::getUrl('edit', ['record' => $record->member_id]))
            ->filters([
                SelectFilter::make('status')->options(MembershipStatus::class),
                SelectFilter::make('membership_tier_id')
                    ->label('Tier')
                    ->options(fn (): array => MembershipTier::orderBy('sort')->pluck('name', 'id')->all()),
                Filter::make('expiring')
                    ->label('Expiring in the next 30 days')
                    ->query(fn (Builder $query) => Membership::whereExpiringWithin($query, 30)),
            ])
            ->recordActions([ActionGroup::make(MembershipTable::actions())])
            ->toolbarActions([
                BulkAction::make('remind')
                    ->label('Send reminders')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->modalDescription('Emails each selected lead member: about payment if it is due, otherwise about renewing.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $sent = $records->filter(fn ($membership): bool => $membership instanceof Membership
                            && app(Memberships::class)->chase($membership))->count();

                        Notification::make()->success()
                            ->title("{$sent} reminder".($sent === 1 ? '' : 's').' sent')
                            ->body($sent < $records->count() ? 'Members with no email address were skipped.' : null)
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMemberships::route('/')];
    }
}
