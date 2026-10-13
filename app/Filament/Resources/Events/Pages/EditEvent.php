<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('register')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->url(fn (Event $record): string => EventResource::getUrl('register', ['record' => $record])),
            Action::make('view')
                ->label('View on site')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Event $record): string => url("/events/{$record->slug}"), shouldOpenInNewTab: true),
            EventResource::duplicateAction(),
            EventResource::cancelAction(),
            DeleteAction::make()
                ->visible(fn (Event $record): bool => $record->bookings()->doesntExist()),
        ];
    }
}
