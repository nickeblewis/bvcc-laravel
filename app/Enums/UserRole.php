<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Membership = 'membership';
    case Events = 'events';
    case Treasurer = 'treasurer';
    case Welfare = 'welfare';
    case Leader = 'leader';
    case Editor = 'editor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Membership => 'Membership secretary',
            self::Events => 'Programme and events',
            self::Treasurer => 'Treasurer',
            self::Welfare => 'Welfare officer',
            self::Leader => 'Session or trip leader',
            self::Editor => 'Website editor',
        };
    }

    /**
     * @return list<Area>
     */
    public function areas(): array
    {
        return match ($this) {
            self::Admin => Area::cases(),
            self::Membership => [Area::Members, Area::Memberships, Area::Payments, Area::Enquiries],
            self::Events => [Area::Members, Area::Events, Area::Bookings, Area::Payments, Area::Enquiries],
            self::Treasurer => [Area::Members, Area::Memberships, Area::Bookings, Area::Payments],
            self::Welfare => [Area::Members, Area::Medical, Area::Bookings],
            self::Leader => [Area::Events, Area::Bookings],
            self::Editor => [Area::Content, Area::Enquiries],
        };
    }

    public function can(Area $area): bool
    {
        return in_array($area, $this->areas(), true);
    }
}
