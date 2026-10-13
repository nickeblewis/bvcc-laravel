<?php

namespace App\Filament\Resources\Memberships\Pages;

use App\Filament\Resources\Memberships\MembershipResource;
use Filament\Resources\Pages\ManageRecords;

/**
 * Memberships are added from the member's record, so this list has no
 * create button.
 */
class ManageMemberships extends ManageRecords
{
    protected static string $resource = MembershipResource::class;

    public function getSubheading(): string
    {
        return 'To add a membership, open the member and use "Add membership".';
    }
}
