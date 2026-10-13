<?php

namespace App\Models\Concerns;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * For things people pay the club for. The model says what is owed with
 * totalPence(); what has been paid is the sum of its payments (refunds are
 * negative).
 */
trait HasPayments
{
    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    abstract public function totalPence(): int;

    public function paidPence(): int
    {
        return (int) ($this->relationLoaded('payments')
            ? $this->payments->sum('amount_pence')
            : $this->payments()->sum('amount_pence'));
    }

    public function balancePence(): int
    {
        return $this->totalPence() - $this->paidPence();
    }
}
