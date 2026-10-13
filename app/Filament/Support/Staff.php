<?php

namespace App\Filament\Support;

use App\Enums\Area;
use App\Models\User;

class Staff
{
    public static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function can(Area $area): bool
    {
        return static::user()?->canManage($area) ?? false;
    }
}
