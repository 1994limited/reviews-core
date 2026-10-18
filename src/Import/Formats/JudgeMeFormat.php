<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

use NineteenNinetyFour\Reviews\Core\Import\Values;

/**
 * Judge.me's export (and its import template), from Shopify. Products are
 * matched on their Shopify handle.
 */
class JudgeMeFormat extends ColumnFormat
{
    public function key(): string
    {
        return 'judgeme';
    }

    public function label(): string
    {
        return 'Judge.me';
    }

    protected function signature(): array
    {
        return ['rating', 'body', 'review_date', 'reviewer_name'];
    }

    protected function columns(): array
    {
        return [
            'id' => ['id', 'review_id'],
            'rating' => ['rating'],
            'title' => ['title'],
            'body' => ['body'],
            'name' => ['reviewer_name'],
            'email' => ['reviewer_email'],
            'date' => ['review_date'],
            'verified' => ['verified', 'verified_buyer'],
            'published' => ['curated', 'published'],
            'reply' => ['reply'],
            'reply_date' => ['reply_date'],
            'photos' => ['picture_urls'],
            'product_handle' => ['product_handle'],
            'product_id' => ['product_id'],
            'product_url' => ['product_url'],
            'product_name' => ['product_title'],
        ];
    }

    protected function published(mixed $value): bool
    {
        // Judge.me curates reviews as "ok", "spam" or "not-yet".
        return ! in_array(mb_strtolower((string) Values::text($value)), ['spam', 'not-yet', 'not_yet', 'false', '0', 'no', 'hidden', 'unpublished'], true);
    }
}
