<?php

namespace NineteenNinetyFour\Reviews\Core\Import;

/**
 * Reading a CSV export: a byte-order mark, commas, semicolons or tabs, and
 * headers written however the platform likes.
 */
final class Csv
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     */
    public static function parse(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $delimiter = self::delimiter($contents);

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return ['headers' => [], 'rows' => []];
        }

        fwrite($handle, $contents);
        rewind($handle);

        $headers = fgetcsv($handle, null, $delimiter, '"', '');

        if (! is_array($headers)) {
            fclose($handle);

            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        $rows = [];

        while (($line = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            if ($line === [null]) {
                continue;
            }

            $row = [];

            foreach ($headers as $i => $header) {
                $row[$header] = (string) ($line[$i] ?? '');
            }

            $rows[] = $row;
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * A header as a key to match on: lower case, words joined by underscores.
     */
    /**
     * A value for a CSV someone may open in a spreadsheet: one starting with
     * =, +, -, @, a tab or a carriage return gets a ' in front, so it's
     * shown as text, never run as a formula.
     */
    public static function cell(mixed $value): string
    {
        $value = $value === null ? '' : (string) (is_bool($value) ? (int) $value : $value);

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /**
     * A value written by cell(), as it was.
     */
    public static function uncell(string $value): string
    {
        return preg_match("/^'[=+\\-@\\t\\r]/", $value) ? substr($value, 1) : $value;
    }

    public static function key(string $header): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($header)), '_');
    }

    private static function delimiter(string $contents): string
    {
        $first = strtok($contents, "\n") ?: '';
        $counts = [',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }
}
