<?php

namespace App\Filament\Resources\CommitteeRoles\Pages;

use App\Filament\Resources\CommitteeRoles\CommitteeRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCommitteeRoles extends ManageRecords
{
    protected static string $resource = CommitteeRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
