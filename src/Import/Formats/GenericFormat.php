<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

/**
 * Any spreadsheet of reviews with columns named the usual ways: a rating
 * (or stars, score), the review (or body, comment, content), a name, a
 * date, and what it's of (a SKU, handle, ID or address). Used when no
 * platform's own format fits.
 */
class GenericFormat extends ColumnFormat
{
    public function key(): string
    {
        return 'generic';
    }

    public function label(): string
    {
        return 'Spreadsheet (CSV or JSON)';
    }

    public function score(array $columns): int
    {
        // Any file with a rating column will do, but last.
        return array_intersect($this->columns()['rating'], $columns) !== [] ? 1 : 0;
    }

    protected function signature(): array
    {
        return [];
    }

    protected function columns(): array
    {
        return [
            'rating' => ['rating', 'stars', 'star_rating', 'score', 'review_rating', 'review_score'],
            'title' => ['title', 'review_title', 'headline', 'subject', 'summary'],
            'body' => ['body', 'review', 'review_body', 'review_text', 'content', 'review_content', 'comment', 'text', 'message'],
            'name' => ['name', 'reviewer_name', 'author', 'author_name', 'reviewer', 'customer_name', 'display_name'],
            'email' => ['email', 'reviewer_email', 'author_email', 'customer_email'],
            'date' => ['date', 'created_at', 'review_date', 'created', 'submitted_at', 'published_at', 'date_created'],
            'verified' => ['verified', 'verified_purchase', 'verified_buyer', 'is_verified'],
            'reply' => ['reply', 'response', 'reply_content', 'merchant_reply', 'owner_reply', 'shop_reply', 'comment_content'],
            'reply_date' => ['reply_date', 'replied_at', 'response_date'],
            'published' => ['published', 'status', 'state', 'curated', 'visible'],
            'photos' => ['photos', 'images', 'picture_urls', 'image_urls', 'media'],
            'id' => ['id', 'review_id', 'external_id'],
            'url' => ['review_url', 'link'],
            'product_sku' => ['product_sku', 'sku', 'variant_sku'],
            'product_handle' => ['product_handle', 'handle', 'slug', 'product_slug'],
            'product_id' => ['product_id', 'entry_id', 'external_product_id', 'productid'],
            'product_url' => ['product_url', 'url', 'page_url'],
            'product_name' => ['product_title', 'product_name', 'product', 'entry_title'],
            'product_gtin' => ['gtin', 'product_gtin', 'barcode', 'ean', 'upc'],
        ];
    }
}
