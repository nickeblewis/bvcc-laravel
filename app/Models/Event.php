<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\FeeType;
use Carbon\CarbonInterface;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property CarbonInterface $starts_at
 * @property CarbonInterface|null $ends_at
 * @property bool $all_day
 * @property int|null $capacity
 * @property CarbonInterface|null $booking_closes_at
 * @property int|null $grade
 * @property FeeType $fee_type
 * @property array<string, int|null>|null $custom_prices
 * @property EventStatus $status
 * @property string|null $series_id
 * @property int|null $leader_id
 * @property-read EventCategory $category
 * @property-read Venue|null $venue
 * @property-read Discipline|null $discipline
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'booking_closes_at' => 'datetime',
            'fee_type' => FeeType::class,
            'custom_prices' => 'array',
            'status' => EventStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event): void {
            if (blank($event->slug)) {
                $event->slug = static::uniqueSlug($event->title, $event->starts_at);
            }
        });
    }

    public static function uniqueSlug(string $title, CarbonInterface $startsAt): string
    {
        $base = Str::slug($title).'-'.$startsAt->format('Y-m-d');
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * @return BelongsTo<EventCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * @return BelongsTo<Discipline, $this>
     */
    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * People holding a place: attendees on bookings that are not cancelled or
     * on the waiting list.
     *
     * @return HasManyThrough<BookingAttendee, Booking, $this>
     */
    public function attendees(): HasManyThrough
    {
        return $this->hasManyThrough(BookingAttendee::class, Booking::class)
            ->whereIn('bookings.status', BookingStatus::holdingAPlace());
    }

    public function placesTaken(): int
    {
        return $this->attendees()->count();
    }

    /**
     * Null when the event has no limit.
     */
    public function placesLeft(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->placesTaken());
    }

    public function isCancelled(): bool
    {
        return $this->status === EventStatus::Cancelled;
    }

    public function isPast(): bool
    {
        return ($this->ends_at ?? $this->starts_at)->isPast();
    }

    public function bookingIsOpen(): bool
    {
        return $this->status === EventStatus::Published
            && ! $this->isPast()
            && ($this->booking_closes_at === null || $this->booking_closes_at->isFuture());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereIn('status', [EventStatus::Published, EventStatus::Cancelled]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', today())->orderBy('starts_at');
    }
}
