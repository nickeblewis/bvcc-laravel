<?php

namespace App\Filament\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    /**
     * Stream rows as a CSV download.
     *
     * @param  list<string>  $headings
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, $headings, escape: '\\');

            foreach ($rows as $row) {
                // Stop spreadsheet apps treating a value as a formula.
                fputcsv($out, array_map(
                    fn ($value) => is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value,
                    $row,
                ), escape: '\\');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
