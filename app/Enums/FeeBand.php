<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The price group a person falls into for sessions and events.
 */
enum FeeBand: string implements HasLabel
{
    case NonMember = 'non_member';
    case PayAsYouPaddle = 'pay_as_you_paddle';
    case Subscriber = 'subscriber';
    case Affiliate = 'affiliate';

    public function getLabel(): string
    {
        return match ($this) {
            self::NonMember => 'Non-member',
            self::PayAsYouPaddle => 'Pay as you paddle',
            self::Subscriber => 'Monthly subscriber',
            self::Affiliate => 'Affiliate',
        };
    }
}
