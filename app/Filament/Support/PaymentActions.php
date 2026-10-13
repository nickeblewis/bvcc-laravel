<?php

namespace App\Filament\Support;

use App\Enums\Area;
use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Membership;
use App\Services\Payments;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * "Record payment" and "Refund" for anything with a balance: used on
 * bookings and memberships, in tables and on the dashboard.
 */
class PaymentActions
{
    public static function record(): Action
    {
        return Action::make('recordPayment')
            ->label('Record payment')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn (Booking|Membership $record): bool => Staff::can(Area::Payments) && $record->balancePence() > 0)
            ->modalHeading(fn (Booking|Membership $record): string => 'Record payment ('.Money::format($record->balancePence()).' due)')
            ->modalWidth('md')
            ->fillForm(fn (Booking|Membership $record): array => [
                'amount_pence' => $record->balancePence(),
                'method' => PaymentMethod::BankTransfer->value,
                'received_on' => today()->toDateString(),
            ])
            ->schema(self::fields())
            ->action(function (array $data, Booking|Membership $record): void {
                self::save($record, $data, sign: 1);

                Notification::make()->success()->title('Payment recorded')->send();
            });
    }

    public static function refund(): Action
    {
        return Action::make('refund')
            ->label('Refund')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->visible(fn (Booking|Membership $record): bool => Staff::can(Area::Payments) && $record->paidPence() > 0)
            ->modalHeading(fn (Booking|Membership $record): string => 'Record a refund ('.Money::format($record->paidPence()).' paid)')
            ->modalDescription('This records that the club has paid money back. It does not move any money itself.')
            ->modalWidth('md')
            ->fillForm(fn (Booking|Membership $record): array => [
                'amount_pence' => $record->paidPence(),
                'method' => PaymentMethod::BankTransfer->value,
                'received_on' => today()->toDateString(),
            ])
            ->schema(self::fields())
            ->action(function (array $data, Booking|Membership $record): void {
                self::save($record, $data, sign: -1);

                Notification::make()->success()->title('Refund recorded')->send();
            });
    }

    /**
     * @return list<TextInput|Select|DatePicker>
     */
    private static function fields(): array
    {
        return [
            MoneyInput::make('amount_pence')->label('Amount')->required()->minValue(0.01),
            Select::make('method')->options(PaymentMethod::class)->required(),
            DatePicker::make('received_on')->label('Date')->required()->maxDate(today()),
            TextInput::make('reference')->maxLength(255),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function save(Booking|Membership $record, array $data, int $sign): void
    {
        $method = $data['method'] instanceof PaymentMethod ? $data['method'] : PaymentMethod::from($data['method']);

        app(Payments::class)->record(
            $record,
            $sign * (int) $data['amount_pence'],
            $method,
            Carbon::parse($data['received_on']),
            $data['reference'] ?? null,
            Staff::user(),
        );
    }
}
