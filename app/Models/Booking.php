<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Models\Concerns\HasPayments;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One person's booking onto an event. It can cover several attendees and is
 * paid for as a whole.
 *
 * @property int $id
 * @property int $event_id
 * @property int|null $member_id
 * @property string $contact_name
 * @property string|null $contact_email
 * @property BookingStatus $status
 * @property CarbonInterface|null $last_chased_at
 * @property-read Event $event
 */
class Booking extends Model
{
    use HasPayments;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'last_chased_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The person who made the booking, when they are on the members list.
     *
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return HasMany<BookingAttendee, $this>
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(BookingAttendee::class);
    }

    public function totalPence(): int
    {
        return (int) ($this->relationLoaded('attendees')
            ? $this->attendees->sum('price_pence')
            : $this->attendees()->sum('price_pence'));
    }

    /**
     * Bring the status in line with what has been paid. Waiting and cancelled
     * bookings are left alone.
     */
    public function refreshStatus(): void
    {
        if (! in_array($this->status, BookingStatus::holdingAPlace(), true)) {
            return;
        }

        $status = $this->balancePence() <= 0 ? BookingStatus::Confirmed : BookingStatus::Pending;

        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }

    /**
     * Bookings with money owed on events that are still going ahead.
     *
     * @param  Builder<self>  $query
     */
    public function scopeToChase(Builder $query): void
    {
        static::whereToChase($query);
    }

    /**
     * The same rule as the scope, for use on any query over bookings.
     * Columns are qualified so it also works when events are joined in.
     */
    public static function whereToChase(QueryBuilder $query): void
    {
        $query->where('bookings.status', BookingStatus::Pending)
            ->whereIn('bookings.event_id', Event::query()->whereNull('cancelled_at')->select('id'));
    }
}
