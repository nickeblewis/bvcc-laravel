<?php

namespace App\Filament\Pages;

use App\Filament\Support\Staff;
use App\Models\Member;
use App\Models\Membership;
use App\Support\Money;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * F-50: a member's own details and membership. Read-only for now; editing
 * details and paying online come later (F-51).
 */
class MyMembership extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'My membership';

    protected static ?string $title = 'My membership';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.my-membership';

    public static function canAccess(): bool
    {
        return Staff::user()?->member_id !== null;
    }

    public function member(): Member
    {
        $member = Staff::user()?->member;
        abort_if($member === null, 403);

        return $member;
    }

    /**
     * The newest membership: the one being paid for, or the current one.
     */
    public function membership(): ?Membership
    {
        return $this->member()->memberships()->with('tier', 'payments')->orderByDesc('starts_on')->first();
    }

    public function money(int $pence): string
    {
        return Money::format($pence);
    }
}
