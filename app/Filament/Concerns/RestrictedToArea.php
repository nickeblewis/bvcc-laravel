<?php

namespace App\Filament\Concerns;

use App\Enums\Area;
use App\Filament\Support\Staff;

/**
 * Limits a resource, page or widget to staff whose role covers an area.
 */
trait RestrictedToArea
{
    abstract public static function area(): Area;

    public static function canAccess(): bool
    {
        return Staff::can(static::area());
    }
}
