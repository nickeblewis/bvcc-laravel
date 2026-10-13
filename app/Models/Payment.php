<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Money received for a membership or a booking. A negative amount is a refund.
 *
 * @property int $id
 * @property int $amount_pence
 * @property PaymentMethod $method
 * @property CarbonInterface $received_on
 * @property-read Membership|Booking $payable
 */
class Payment extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'received_on' => 'date',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * A short description of what was paid for, for the ledger.
     */
    public function describe(): string
    {
        $payable = $this->payable;

        return match (true) {
            $payable instanceof Membership => "Membership: {$payable->lead->name} ({$payable->tier->name})",
            $payable instanceof Booking => "Booking: {$payable->contact_name}, {$payable->event->title}",
        };
    }
}
