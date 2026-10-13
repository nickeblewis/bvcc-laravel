<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Waiting = 'waiting';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Confirmed => 'Paid',
            self::Waiting => 'Waiting list',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Waiting => 'info',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Statuses that hold a place on the event.
     *
     * @return list<self>
     */
    public static function holdingAPlace(): array
    {
        return [self::Pending, self::Confirmed];
    }
}
