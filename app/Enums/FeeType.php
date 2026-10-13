<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FeeType: string implements HasLabel
{
    case Session = 'session';
    case HalfDay = 'half_day';
    case FullDay = 'full_day';
    case Competition = 'competition';
    case Custom = 'custom';
    case Free = 'free';

    public function getLabel(): string
    {
        return match ($this) {
            self::Session => 'Session',
            self::HalfDay => 'Half day (up to 4 hours)',
            self::FullDay => 'Full day (over 4 hours)',
            self::Competition => 'League or competition',
            self::Custom => 'Custom prices',
            self::Free => 'Free',
        };
    }

    /**
     * The types that have a row in the club fee table.
     *
     * @return list<self>
     */
    public static function standard(): array
    {
        return [self::Session, self::HalfDay, self::FullDay, self::Competition];
    }
}
