<?php
declare(strict_types=1);

namespace IMS\Services;

/**
 * Enterprise CSV & JSON Data Export / Import Engine
 * Enables bulk product catalog ingest, transaction ledger exports, and data backups.
 */
class ExportImportService
{
    /**
     * Export array of database records to standardized CSV string.
     */
    public function exportToCsv(array $data, array $headers): string
    {
        $output = fopen('php://temp', 'r+');

        // Write header row
        fputcsv($output, $headers);

        foreach ($data as $row) {
            $line = [];
            foreach ($headers as $key => $title) {
                $fieldKey = is_string($key) ? $key : $title;
                $line[] = $row[$fieldKey] ?? ($row[$title] ?? '');
            }
            fputcsv($output, $line);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }

    /**
     * Parse and validate uploaded CSV string into validated product records.
     */
    public function parseProductsCsv(string $csvContent): array
    {
        $lines = explode("\n", trim($csvContent));
        if (count($lines) < 2) {
            return ['success' => false, 'error' => 'CSV file is empty or missing headers.', 'items' => []];
        }

        $headerRow = str_getcsv(array_shift($lines));
        $normalizedHeaders = array_map(fn($h) => strtolower(trim($h)), $headerRow);

        $parsed = [];
        $errors = [];
        $lineNum = 1;

        foreach ($lines as $line) {
            $lineNum++;
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $fields = str_getcsv($trimmed);
            if (count($fields) < count($normalizedHeaders)) {
                $errors[] = "Line {$lineNum}: Column count mismatch.";
                continue;
            }

            $row = array_combine($normalizedHeaders, array_slice($fields, 0, count($normalizedHeaders)));

            $name = trim($row['name'] ?? ($row['product_name'] ?? ''));
            $price = (float) ($row['price'] ?? 0.0);
            $qty = (int) ($row['quantity'] ?? ($row['qty'] ?? 0));

            if ($name === '' || $price < 0 || $qty < 0) {
                $errors[] = "Line {$lineNum}: Invalid product name, price, or quantity.";
                continue;
            }

            $parsed[] = [
                'name' => $name,
                'category' => trim($row['category'] ?? 'General'),
                'sku' => trim($row['sku'] ?? 'SKU-' . strtoupper(substr(md5($name), 0, 6))),
                'price' => $price,
                'cost_price' => (float) ($row['cost_price'] ?? ($row['cost'] ?? 0.0)),
                'quantity' => $qty,
                'alert_quantity' => (int) ($row['alert_quantity'] ?? 10)
            ];
        }

        return [
            'success' => count($parsed) > 0,
            'parsed_count' => count($parsed),
            'error_count' => count($errors),
            'errors' => $errors,
            'items' => $parsed
        ];
    }
}
