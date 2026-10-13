<?php

namespace App\Filament\Support;

use App\Enums\Area;
use App\Enums\MembershipStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Services\Memberships;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

/**
 * Columns and row actions for memberships, shared by the memberships list
 * and the tab on a member's record.
 */
class MembershipTable
{
    /**
     * @return list<TextColumn>
     */
    public static function columns(bool $withMember = true): array
    {
        return [
            TextColumn::make('lead.last_name')
                ->label('Member')
                ->formatStateUsing(fn (Membership $record): string => $record->lead->name)
                ->description(fn (Membership $record): ?string => ($n = $record->members_count - 1) > 0 ? "+ {$n} family" : null)
                ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('lead', fn (Builder $lead) => Member::whereMatches($lead, $search)))
                ->sortable()
                ->visible($withMember),
            TextColumn::make('tier.name')->label('Tier')->wrap(),
            TextColumn::make('starts_on')->date('j M Y')->sortable(),
            TextColumn::make('expires_on')
                ->date('j M Y')
                ->sortable()
                ->color(fn (Membership $record): ?string => $record->expiresWithin(30) ? 'warning' : null)
                ->description(fn (Membership $record): ?string => $record->expiresWithin(30) ? 'Expiring soon' : null),
            TextColumn::make('status')->badge(),
            TextColumn::make('balance')
                ->label('To pay')
                ->state(fn (Membership $record): string => Money::format(max(0, $record->balancePence())))
                ->color(fn (Membership $record): ?string => $record->balancePence() > 0 ? 'danger' : null),
        ];
    }

    /**
     * @return list<Action>
     */
    public static function actions(): array
    {
        return [
            PaymentActions::record(),
            static::chase(),
            static::renew(),
            PaymentActions::refund(),
            static::cancel(),
        ];
    }

    public static function chase(): Action
    {
        return Action::make('chase')
            ->label('Send reminder')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('warning')
            ->visible(fn (Membership $record): bool => filled($record->lead->email)
                && ($record->status === MembershipStatus::Pending || $record->expiresWithin(30) || $record->status === MembershipStatus::Expired))
            ->requiresConfirmation()
            ->modalDescription(fn (Membership $record): string => "Email {$record->lead->email} about "
                .($record->status === MembershipStatus::Pending ? 'the payment due' : 'renewing').'?'
                .($record->last_chased_at ? ' Last reminded '.$record->last_chased_at->diffForHumans().'.' : ''))
            ->action(function (Membership $record): void {
                app(Memberships::class)->chase($record);

                Notification::make()->success()->title('Reminder sent')->send();
            });
    }

    public static function renew(): Action
    {
        return Action::make('renew')
            ->label('Renew')
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn (Membership $record): bool => Staff::can(Area::Memberships)
                && in_array($record->status, [MembershipStatus::Active, MembershipStatus::Expired], true)
                && $record->expires_on->lte(today()->addDays(60)))
            ->requiresConfirmation()
            ->modalDescription('Creates the next year on the same tier for the same people. It will wait for payment if the tier has a fee.')
            ->action(function (Membership $record): void {
                $new = app(Memberships::class)->renew($record);

                Notification::make()->success()
                    ->title('Renewed until '.$new->expires_on->format('j M Y'))
                    ->send();
            });
    }

    public static function cancel(): Action
    {
        return Action::make('cancelMembership')
            ->label('Cancel')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Membership $record): bool => Staff::can(Area::Memberships)
                && in_array($record->status, [MembershipStatus::Active, MembershipStatus::Pending], true))
            ->requiresConfirmation()
            ->action(function (Membership $record): void {
                app(Memberships::class)->cancel($record);

                Notification::make()->success()->title('Membership cancelled')->send();
            });
    }
}
