<?php

namespace NineteenNinetyFour\Reviews\Core\Import\Formats;

use NineteenNinetyFour\Reviews\Core\Import\Values;

/**
 * Shopify's retired Product Reviews app's export. Products are matched on
 * their Shopify handle.
 */
class ShopifyFormat extends ColumnFormat
{
    public function key(): string
    {
        return 'shopify';
    }

    public function label(): string
    {
        return 'Shopify Product Reviews';
    }

    protected function signature(): array
    {
        return ['product_handle', 'state', 'rating', 'author', 'body'];
    }

    protected function columns(): array
    {
        return [
            'rating' => ['rating'],
            'title' => ['title'],
            'body' => ['body'],
            'name' => ['author'],
            'email' => ['email'],
            'date' => ['created_at'],
            'published' => ['state'],
            'reply' => ['reply'],
            'reply_date' => ['replied_at'],
            'product_handle' => ['product_handle'],
            'product_id' => ['product_id'],
        ];
    }

    protected function published(mixed $value): bool
    {
        return mb_strtolower((string) Values::text($value)) === 'published';
    }
}
