<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The part of the public site a page lives under. The value is the URL prefix.
 */
enum PageSection: string implements HasLabel
{
    case Paddle = 'paddle';
    case Join = 'join';
    case Club = 'club';

    public function getLabel(): string
    {
        return match ($this) {
            self::Paddle => 'Paddle with us',
            self::Join => 'Join',
            self::Club => 'Club',
        };
    }
}
