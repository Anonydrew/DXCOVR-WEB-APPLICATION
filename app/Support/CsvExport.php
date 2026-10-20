<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams CSV downloads. Cells that a spreadsheet could interpret as a
 * formula (=, +, -, @, tab, carriage return) are prefixed with an apostrophe
 * to prevent CSV/formula injection.
 */
final class CsvExport
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headings, escape: '');

            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'cell'], $row), escape: '');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public static function cell(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
