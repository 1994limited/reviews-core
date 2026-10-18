<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;
use NineteenNinetyFour\Reviews\Core\Import\Values;

/**
 * Yotpo's reviews export (Moderation → Export). Site reviews have the
 * product "yotpo_site_reviews": they're about the business.
 */
class YotpoFormat extends ColumnFormat
{
    public function key(): string
    {
        return 'yotpo';
    }

    public function label(): string
    {
        return 'Yotpo';
    }

    protected function signature(): array
    {
        return ['review_score', 'review_content'];
    }

    protected function columns(): array
    {
        return [
            'id' => ['review_id'],
            'rating' => ['review_score'],
            'title' => ['review_title'],
            'body' => ['review_content'],
            'name' => ['reviewer_display_name', 'reviewer_name'],
            'email' => ['reviewer_email'],
            'date' => ['review_creation_date', 'date'],
            'verified' => ['reviewer_type'],
            'published' => ['review_status'],
            'reply' => ['comment_content'],
            'reply_date' => ['comment_date'],
            'photos' => ['published_image_url', 'published_image_urls'],
            'product_sku' => ['product_sku'],
            'product_id' => ['product_id'],
            'product_handle' => ['product_handle'],
            'product_url' => ['product_url'],
            'product_name' => ['product_title', 'product_name'],
        ];
    }

    public function map(array $row): ?ImportedReview
    {
        $review = parent::map($row);

        if ($review === null || ($review->product['sku'] ?? null) !== 'yotpo_site_reviews') {
            return $review;
        }

        return new ImportedReview(...['product' => []] + get_object_vars($review));
    }

    protected function published(mixed $value): bool
    {
        return in_array(mb_strtolower((string) Values::text($value)), ['published', 'approved'], true);
    }
}
