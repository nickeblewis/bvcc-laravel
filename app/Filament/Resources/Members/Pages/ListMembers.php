<?php

namespace App\Filament\Resources\Members\Pages;

use App\Enums\Area;
use App\Filament\Resources\Members\MemberResource;
use App\Filament\Support\Csv;
use App\Filament\Support\Staff;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->export()),
            CreateAction::make(),
        ];
    }

    /**
     * Exports the rows currently shown, with the table's filters and search
     * applied. Emergency and medical details are included for welfare staff only.
     */
    private function export(): StreamedResponse
    {
        $withMedical = Staff::can(Area::Medical);

        $headings = ['First name', 'Last name', 'Email', 'Phone', 'Date of birth', 'Town', 'Postcode', 'Membership', 'Expires', 'Paddle UK number', 'Photo consent'];

        if ($withMedical) {
            array_push($headings, 'Emergency contacts', 'Medical notes');
        }

        $rows = $this->getFilteredSortedTableQuery()?->get()->map(function ($member) use ($withMedical): array {
            /** @var Member $member */
            $membership = $member->memberships->first();

            $row = [
                $member->first_name, $member->last_name, $member->email, $member->phone,
                $member->date_of_birth?->toDateString(), $member->town, $member->postcode,
                $membership?->tier->name, $membership?->expires_on->toDateString(),
                $member->paddle_uk_number, $member->photo_consent ? 'Yes' : 'No',
            ];

            if ($withMedical) {
                $row[] = collect($member->emergency_contacts)
                    ->map(fn (array $c): string => trim(($c['name'] ?? '').' ('.($c['relationship'] ?? '').') '.($c['phone'] ?? '')))
                    ->implode('; ');
                $row[] = $member->medical_notes;
            }

            return $row;
        }) ?? [];

        return Csv::download('members-'.today()->toDateString().'.csv', $headings, $rows);
    }
}
