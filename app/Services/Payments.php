<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;

class Payments
{
    /**
     * Record money received (or refunded, with a negative amount) and update
     * the status of whatever it was for.
     */
    public function record(
        Booking|Membership $payable,
        int $amountPence,
        PaymentMethod $method,
        ?CarbonInterface $receivedOn = null,
        ?string $reference = null,
        ?User $recordedBy = null,
        ?string $notes = null,
    ): Payment {
        $payment = $payable->payments()->create([
            'amount_pence' => $amountPence,
            'method' => $method,
            'received_on' => $receivedOn ?? today(),
            'reference' => $reference,
            'notes' => $notes,
            'recorded_by' => $recordedBy?->id,
        ]);

        $payable->unsetRelation('payments');
        $payable->refreshStatus();

        return $payment;
    }
}
