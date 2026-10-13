<?php

namespace App\Models;

use App\Enums\FeeBand;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $booking_id
 * @property int|null $member_id
 * @property string $name
 * @property FeeBand $fee_band
 * @property int $price_pence
 * @property string|null $waived_reason
 * @property CarbonInterface|null $attended_at
 * @property-read Booking $booking
 * @property-read Member|null $member
 */
class BookingAttendee extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee_band' => FeeBand::class,
            'attended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
