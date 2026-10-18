<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

use NineteenNinetyFour\Reviews\Core\Import\Format;
use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;
use NineteenNinetyFour\Reviews\Core\Import\Values;

/**
 * A format described by which columns hold what. Each field lists the
 * column names it might have, first match wins.
 */
abstract class ColumnFormat implements Format
{
    /**
     * The columns, by what they hold: rating, title, body, name, email,
     * date, verified, reply, reply_date, published, photos, id, url, and
     * product_sku, product_handle, product_id, product_url, product_name,
     * product_gtin.
     *
     * @return array<string, array<int, string>>
     */
    abstract protected function columns(): array;

    /**
     * The columns that must be there for a file to be this format.
     *
     * @return array<int, string>
     */
    abstract protected function signature(): array;

    /** The scale the rating column is on. */
    protected function scale(): int
    {
        return 5;
    }

    public function score(array $columns): int
    {
        $signature = $this->signature();

        if ($signature === [] || array_diff($signature, $columns) !== []) {
            return 0;
        }

        return 10 * count($signature);
    }

    public function map(array $row): ?ImportedReview
    {
        $rating = Values::rating($this->value($row, 'rating'), $this->scale());

        if ($rating === null) {
            return null;
        }

        $product = [];

        foreach (['sku', 'handle', 'id', 'url', 'name', 'gtin'] as $key) {
            if (($value = Values::text($this->value($row, 'product_'.$key))) !== null) {
                $product[$key] = $value;
            }
        }

        $published = $this->value($row, 'published');

        return new ImportedReview(
            source: $this->key(),
            sourceId: Values::text($this->value($row, 'id')),
            product: $product,
            rating: $rating,
            title: Values::text($this->value($row, 'title')),
            body: Values::text($this->value($row, 'body')),
            name: (string) Values::text($this->value($row, 'name')),
            email: Values::email($this->value($row, 'email')),
            date: Values::date($this->value($row, 'date')),
            verified: Values::bool($this->value($row, 'verified')),
            reply: Values::text($this->value($row, 'reply')),
            replyDate: Values::date($this->value($row, 'reply_date')),
            published: $published === null || Values::text($published) === null ? true : $this->published($published),
            photos: Values::urls($this->value($row, 'photos')),
            url: Values::url($this->value($row, 'url')),
        );
    }

    /**
     * Whether the platform had the review on show.
     */
    protected function published(mixed $value): bool
    {
        $text = mb_strtolower((string) Values::text($value));

        return ! in_array($text, ['0', 'false', 'no', 'unpublished', 'hidden', 'rejected', 'spam', 'pending', 'archived'], true);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function value(array $row, string $field): mixed
    {
        foreach ($this->columns()[$field] ?? [] as $column) {
            if (array_key_exists($column, $row) && ((is_array($row[$column]) && $row[$column] !== []) || Values::text($row[$column]) !== null)) {
                return $row[$column];
            }
        }

        return null;
    }
}
