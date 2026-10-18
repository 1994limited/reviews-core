<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

use NineteenNinetyFour\Reviews\Core\Import\Values;

/**
 * WooCommerce product reviews. WooCommerce has no export of its own: this
 * reads the WordPress comment columns that review export plugins (and WP
 * All Export) give. Products are matched on their SKU, or their URL.
 */
class WooCommerceFormat extends ColumnFormat
{
    public function key(): string
    {
        return 'woocommerce';
    }

    public function label(): string
    {
        return 'WooCommerce';
    }

    protected function signature(): array
    {
        return ['comment_content', 'rating'];
    }

    protected function columns(): array
    {
        return [
            'id' => ['comment_id'],
            'rating' => ['rating'],
            'body' => ['comment_content'],
            'name' => ['comment_author'],
            'email' => ['comment_author_email'],
            'date' => ['comment_date_gmt', 'comment_date'],
            'verified' => ['verified'],
            'published' => ['comment_approved'],
            'product_sku' => ['product_sku', 'sku'],
            'product_url' => ['product_url', 'comment_post_url'],
            'product_name' => ['product_title', 'product_name', 'comment_post_title'],
            'product_id' => ['comment_post_id', 'product_id'],
        ];
    }

    protected function published(mixed $value): bool
    {
        // WordPress keeps "1" for approved, "0" for waiting, and "spam" or "trash".
        return in_array(mb_strtolower((string) Values::text($value)), ['1', 'approved'], true);
    }
}
