<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

use NineteenNinetyFour\Reviews\Core\Import\Csv;
use NineteenNinetyFour\Reviews\Core\Import\Values;

/**
 * Reviews' own export (`reviews:export`, and the Control Panel's), from the
 * Statamic or Craft version: for moving between sites or platforms. Photos are files on the old site, so they don't come with it.
 */
class ReviewsFormat extends ColumnFormat
{
    public function key(): string
    {
        return 'reviews';
    }

    public function label(): string
    {
        return 'Reviews for Statamic or Craft (export)';
    }

    protected function signature(): array
    {
        return ['entry', 'rating', 'status', 'created_at'];
    }

    protected function columns(): array
    {
        return [
            'rating' => ['rating'],
            'title' => ['title'],
            'body' => ['body'],
            'name' => ['name'],
            'email' => ['email'],
            'date' => ['created_at'],
            'verified' => ['verified'],
            'reply' => ['reply'],
            'reply_date' => ['replied_at'],
            'published' => ['status'],
            'id' => ['id'],
            'product_id' => ['entry'],
            'product_name' => ['entry_title'],
            'url' => ['source_url'],
        ];
    }

    /**
     * The export guards cells against spreadsheet formulas (Csv::cell):
     * undone here.
     */
    protected function value(array $row, string $field): mixed
    {
        $value = parent::value($row, $field);

        return is_string($value) ? Csv::uncell($value) : $value;
    }

    protected function published(mixed $value): bool
    {
        return Values::text($value) === 'approved';
    }
}
