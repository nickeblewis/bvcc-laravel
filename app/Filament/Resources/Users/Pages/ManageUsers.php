<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        // An administrator vouches for a staff login, so it needs no email check.
        return [CreateAction::make()->after(fn (User $record) => $record->markEmailAsVerified())];
    }
}
