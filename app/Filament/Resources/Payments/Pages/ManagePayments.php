<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Support\Csv;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => Csv::download(
                    'payments-'.today()->toDateString().'.csv',
                    ['Date', 'For', 'Amount', 'Method', 'Reference', 'Recorded by'],
                    $this->getFilteredSortedTableQuery()?->get()->map(function ($payment): array {
                        /** @var Payment $payment */
                        return [
                            $payment->received_on->toDateString(),
                            $payment->describe(),
                            number_format($payment->amount_pence / 100, 2, '.', ''),
                            $payment->method->getLabel(),
                            $payment->getAttribute('reference'),
                            $payment->recorder?->name,
                        ];
                    }) ?? [],
                )),
        ];
    }
}
