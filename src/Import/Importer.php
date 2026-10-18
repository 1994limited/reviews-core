<?php

namespace NineteenNinetyFour\Reviews\Core\Import;

/**
 * Reads an export file (CSV or JSON) into reviews, working out which
 * platform's format it is unless told. Every row is read, whatever its
 * rating: an import never picks and chooses.
 */
final class Importer
{
    /** @var array<string, Format> */
    private array $formats = [];

    /**
     * @param  array<int, Format>  $formats
     */
    public function __construct(array $formats = [])
    {
        foreach ([...Formats::all(), ...$formats] as $format) {
            $this->formats[$format->key()] = $format;
        }
    }

    /**
     * @return array<string, Format>
     */
    public function formats(): array
    {
        return $this->formats;
    }

    public function read(string $contents, ?string $format = null): ImportResult
    {
        [$columns, $rows] = $this->rows($contents);

        $chosen = $format !== null ? ($this->formats[$format] ?? null) : $this->detect($columns);

        if ($chosen === null) {
            return new ImportResult(null, [], [['row' => 0, 'reason' => 'The file isn’t a format Reviews can read: it needs a rating column at least.']]);
        }

        $reviews = [];
        $skipped = [];

        foreach ($rows as $i => $row) {
            $review = $chosen->map($row);

            if ($review === null) {
                $skipped[] = ['row' => $i + 1, 'reason' => 'No rating from 1 to 5.'];

                continue;
            }

            $reviews[] = $review;
        }

        return new ImportResult($chosen, $reviews, $skipped);
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function detect(array $columns): ?Format
    {
        $best = null;
        $score = 0;

        foreach ($this->formats as $format) {
            $candidate = $format->score($columns);

            if ($candidate > $score) {
                [$best, $score] = [$format, $candidate];
            }
        }

        return $best;
    }

    /**
     * The file's columns and rows, keyed by Csv::key() of each column.
     *
     * @return array{0: array<int, string>, 1: array<int, array<string, mixed>>}
     */
    private function rows(string $contents): array
    {
        $trimmed = ltrim(preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents);

        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            $data = json_decode($trimmed, true);

            if (is_array($data)) {
                $list = array_is_list($data) ? $data : ($data['reviews'] ?? $data['data'] ?? []);
                $rows = array_values(array_filter(array_map(fn ($row) => is_array($row) ? $this->flatten($row) : null, (array) $list)));
                $columns = array_values(array_unique(array_merge(...array_map('array_keys', $rows ?: [[]]))));

                return [$columns, $rows];
            }
        }

        $csv = Csv::parse($contents);
        $columns = array_map(fn ($header) => Csv::key($header), $csv['headers']);
        $rows = array_map(function (array $row) {
            $keyed = [];

            foreach ($row as $header => $value) {
                $keyed[Csv::key((string) $header)] = $value;
            }

            return $keyed;
        }, $csv['rows']);

        return [$columns, $rows];
    }

    /**
     * Nested JSON as one level, "product.sku" becoming "product_sku".
     *
     * @param  array<mixed>  $row
     * @return array<string, mixed>
     */
    private function flatten(array $row, string $prefix = ''): array
    {
        $flat = [];

        foreach ($row as $key => $value) {
            $name = Csv::key($prefix.$key);

            if (is_array($value) && ! array_is_list($value)) {
                $flat += $this->flatten($value, $name.'_');
            } else {
                $flat[$name] = $value;
            }
        }

        return $flat;
    }
}
