<?php

namespace App\Filament\Resources\Payments;

use App\Enums\Area;
use App\Enums\PaymentMethod;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Booking;
use App\Models\Membership;
use App\Models\Payment;
use App\Support\Money;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use UnitEnum;

/**
 * The ledger: every payment and refund recorded, read-only. Payments are
 * added from the booking or membership they are for.
 */
class PaymentResource extends Resource
{
    use RestrictedToArea;

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Club';

    protected static ?int $navigationSort = 3;

    public static function area(): Area
    {
        return Area::Payments;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('recorder')
                ->with('payable', fn (Relation $payable) => $payable instanceof MorphTo
                    ? $payable->morphWith([Membership::class => ['lead', 'tier'], Booking::class => ['event']])
                    : $payable))
            ->defaultSort('received_on', 'desc')
            ->columns([
                TextColumn::make('received_on')->label('Date')->date('j M Y')->sortable(),
                TextColumn::make('for')
                    ->state(fn (Payment $record): string => $record->describe())
                    ->wrap(),
                TextColumn::make('amount_pence')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state): string => Money::format((int) $state))
                    ->color(fn ($state): ?string => $state < 0 ? 'danger' : null)
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state): string => Money::format((int) $state))),
                TextColumn::make('method')->badge()->color('gray'),
                TextColumn::make('reference')->searchable()->toggleable(),
                TextColumn::make('recorder.name')->label('Recorded by')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('method')->options(PaymentMethod::class),
                SelectFilter::make('payable_type')
                    ->label('For')
                    ->options([
                        (new Membership)->getMorphClass() => 'Memberships',
                        (new Booking)->getMorphClass() => 'Bookings',
                    ]),
                Filter::make('received')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('received_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('received_on', '<=', $date))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePayments::route('/')];
    }
}
