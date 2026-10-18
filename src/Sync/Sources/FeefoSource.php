<?php

namespace NineteenNinetyFour\Reviews\Core\Sync\Sources;

use DateTimeImmutable;
use Generator;
use NineteenNinetyFour\Reviews\Core\Http\Client;
use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;
use NineteenNinetyFour\Reviews\Core\Import\Values;
use NineteenNinetyFour\Reviews\Core\Sync\Attribution;

/**
 * Feefo (Reviews API v20). Reviews need no key: only the merchant
 * identifier. One Feefo review can rate the service and several products;
 * each becomes a review of its own. Feefo documents building product
 * schema from its API, so its product reviews may go in schema.
 */
class FeefoSource extends PagedSource
{
    public const URL = 'https://api.feefo.com/api/20/reviews/all';

    public function key(): string
    {
        return 'feefo';
    }

    public function label(): string
    {
        return 'Feefo';
    }

    public function fields(): array
    {
        return [
            'merchant' => self::field(['label' => 'Merchant identifier', 'help' => 'From your Feefo Hub, or the address of your Feefo reviews page.']),
        ];
    }

    public function attribution(): ?Attribution
    {
        return new Attribution('Feefo', 'https://www.feefo.com');
    }

    public function schemaAllowed(): bool
    {
        return true;
    }

    public function fetch(Client $http, array $config, ?DateTimeImmutable $since = null): Generator
    {
        $merchant = $this->setting($config, 'merchant');

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $reply = $this->get($http, self::URL, ['merchant_identifier' => $merchant, 'page' => $page, 'page_size' => 100]);
            $items = is_array($reply['reviews'] ?? null) ? $reply['reviews'] : [];

            foreach ($items as $item) {
                yield from $this->reviews(is_array($item) ? $item : []);
            }

            $pages = (int) (self::dig($reply, 'summary.meta.pages') ?? self::dig($reply, 'meta.pages') ?? 0);

            if ($items === [] || $page >= $pages) {
                return;
            }
        }
    }

    /**
     * @param  array<mixed>  $item
     * @return Generator<int, ImportedReview>
     */
    protected function reviews(array $item): Generator
    {
        $name = (string) Values::text(self::dig($item, 'customer.display_name'));
        $url = Values::text($item['url'] ?? null);

        if (is_array($item['service'] ?? null) && ($rating = Values::rating(self::dig($item, 'service.rating.rating'))) !== null) {
            $service = $item['service'];

            yield new ImportedReview(
                source: $this->key(),
                sourceId: Values::text($service['id'] ?? null) ?? Values::text($item['merchant']['identifier'] ?? null),
                product: [],
                rating: $rating,
                title: Values::text($service['title'] ?? null),
                body: Values::text($service['review'] ?? null),
                name: $name,
                date: Values::date($service['created_at'] ?? null),
                verified: true,
                reply: $this->reply($service),
                url: $url,
            );
        }

        foreach (is_array($item['products'] ?? null) ? $item['products'] : [] as $product) {
            if (! is_array($product) || ($rating = Values::rating(self::dig($product, 'rating.rating'))) === null) {
                continue;
            }

            yield new ImportedReview(
                source: $this->key(),
                sourceId: Values::text($product['id'] ?? null),
                product: array_filter([
                    'sku' => Values::text(self::dig($product, 'product.sku')),
                    'url' => Values::text(self::dig($product, 'product.url')),
                    'name' => Values::text(self::dig($product, 'product.title')),
                ]),
                rating: $rating,
                title: Values::text($product['title'] ?? null),
                body: Values::text($product['review'] ?? null),
                name: $name,
                date: Values::date($product['created_at'] ?? null),
                verified: true,
                reply: $this->reply($product),
                photos: $this->photos($product),
                url: $url,
            );
        }
    }

    /**
     * @param  array<mixed>  $review
     */
    protected function reply(array $review): ?string
    {
        foreach (is_array($review['thread'] ?? null) ? $review['thread'] : [] as $post) {
            if (is_array($post) && ($text = Values::text($post['merchant_comment'] ?? null)) !== null) {
                return $text;
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $review
     * @return array<int, string>
     */
    protected function photos(array $review): array
    {
        return Values::urls(array_map(fn ($media) => is_array($media) ? ($media['url'] ?? null) : $media, is_array($review['media'] ?? null) ? $review['media'] : []));
    }
}
