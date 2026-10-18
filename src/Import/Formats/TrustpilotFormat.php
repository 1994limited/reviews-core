<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

/**
 * Trustpilot Business's review export. These are reviews of the business,
 * unless a product's SKU is given, and Trustpilot asks that they're shown
 * with its logo and a link to your profile.
 */
class TrustpilotFormat extends ColumnFormat
{
    public function key(): string
    {
        return 'trustpilot';
    }

    public function label(): string
    {
        return 'Trustpilot';
    }

    protected function signature(): array
    {
        return ['review_stars', 'review_content'];
    }

    protected function columns(): array
    {
        return [
            'id' => ['review_id'],
            'rating' => ['review_stars'],
            'title' => ['review_title'],
            'body' => ['review_content'],
            'name' => ['consumer_name', 'review_consumer_name'],
            'email' => ['consumer_email', 'review_consumer_email'],
            'date' => ['review_created_utc', 'review_created', 'review_date'],
            'verified' => ['review_verified', 'verified'],
            'reply' => ['reply_content', 'company_reply'],
            'reply_date' => ['reply_date_utc', 'company_reply_date_utc', 'reply_date'],
            'url' => ['review_url'],
            'product_sku' => ['product_sku', 'sku'],
        ];
    }
}
