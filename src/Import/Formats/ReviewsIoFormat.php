<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

/**
 * REVIEWS.io's review export and import template.
 */
class ReviewsIoFormat extends ColumnFormat
{
    public function key(): string
    {
        return 'reviewsio';
    }

    public function label(): string
    {
        return 'REVIEWS.io';
    }

    protected function signature(): array
    {
        return ['star_rating', 'comment'];
    }

    protected function columns(): array
    {
        return [
            'id' => ['review_id', 'id'],
            'rating' => ['star_rating'],
            'title' => ['review_title', 'title'],
            'body' => ['comment', 'comments', 'review'],
            'name' => ['name', 'reviewer_name'],
            'email' => ['email_address', 'email'],
            'date' => ['date', 'date_created'],
            'verified' => ['verified', 'verified_buyer'],
            'reply' => ['replies', 'reply'],
            'photos' => ['images', 'photos'],
            'product_sku' => ['sku', 'product_sku'],
            'product_name' => ['product_name'],
        ];
    }
}
