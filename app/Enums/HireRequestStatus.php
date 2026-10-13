<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum HireRequestStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Approved = 'approved';
    case Declined = 'declined';
    case Returned = 'returned';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Approved => 'success',
            self::Declined => 'danger',
            self::Returned => 'gray',
        };
    }
}
