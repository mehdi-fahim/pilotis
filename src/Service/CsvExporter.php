<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class CsvExporter
{
    /**
     * @param list<string>                 $headers
     * @param iterable<int, list<string|int|float|null>> $rows
     */
    public function export(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // BOM UTF-8 for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    static fn (mixed $value): string => $value === null ? '' : (string) $value,
                    $row
                ), ';');
            }

            fclose($handle);
        });

        $safeName = preg_replace('/[^a-zA-Z0-9_\-.]+/', '_', $filename) ?: 'export.csv';
        if (!str_ends_with(strtolower($safeName), '.csv')) {
            $safeName .= '.csv';
        }

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $safeName . '"');

        return $response;
    }
}
