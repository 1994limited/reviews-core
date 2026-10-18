<?php

namespace NineteenNinetyFour\Reviews\Core\Sync\Sources;

use DateTimeImmutable;
use Generator;
use NineteenNinetyFour\Reviews\Core\Http\Client;
use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;
use NineteenNinetyFour\Reviews\Core\Import\Values;
use NineteenNinetyFour\Reviews\Core\Sync\Attribution;

/**
 * Judge.me (Shopify). Products come with their Shopify ID and handle, so
 * match them on the handle. Judge.me's API doesn't include replies.
 */
class JudgeMeSource extends PagedSource
{
    public const URL = 'https://api.judge.me/api/v1/reviews';

    public function key(): string
    {
        return 'judgeme';
    }

    public function label(): string
    {
        return 'Judge.me';
    }

    public function fields(): array
    {
        return [
            'shop_domain' => self::field(['label' => 'Shop domain', 'help' => 'Your myshopify.com domain, such as dog-ear-books.myshopify.com.']),
            'api_token' => self::field(['label' => 'Private API token', 'help' => 'Judge.me → Settings → Integrations → View API token.'], secret: true),
        ];
    }

    public function attribution(): ?Attribution
    {
        return new Attribution('Judge.me', 'https://judge.me');
    }

    public function fetch(Client $http, array $config, ?DateTimeImmutable $since = null): Generator
    {
        $domain = $this->setting($config, 'shop_domain');
        $token = $this->setting($config, 'api_token');

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $reply = $this->get($http, self::URL, ['shop_domain' => $domain, 'api_token' => $token, 'per_page' => 100, 'page' => $page]);
            $items = is_array($reply['reviews'] ?? null) ? $reply['reviews'] : [];

            foreach ($items as $item) {
                if (is_array($item) && ($review = $this->review($item)) !== null) {
                    yield $review;
                }
            }

            if (count($items) < 100) {
                return;
            }
        }
    }

    /**
     * @param  array<mixed>  $item
     */
    protected function review(array $item): ?ImportedReview
    {
        $rating = Values::rating($item['rating'] ?? null);

        if ($rating === null) {
            return null;
        }

        $photos = array_map(fn ($picture) => is_array($picture) ? (self::dig($picture, 'urls.original') ?? self::dig($picture, 'urls.huge') ?? $picture['url'] ?? null) : $picture, is_array($item['pictures'] ?? null) ? $item['pictures'] : []);

        $published = ($item['published'] ?? true) && ! Values::bool($item['hidden'] ?? false);

        return new ImportedReview(
            source: $this->key(),
            sourceId: Values::text($item['id'] ?? null),
            product: array_filter([
                'handle' => Values::text($item['product_handle'] ?? null),
                'id' => Values::text($item['product_external_id'] ?? null),
                'name' => Values::text($item['product_title'] ?? null),
            ]),
            rating: $rating,
            title: Values::text($item['title'] ?? null),
            body: Values::text($item['body'] ?? null),
            name: (string) Values::text(self::dig($item, 'reviewer.name')),
            email: Values::email(self::dig($item, 'reviewer.email')),
            date: Values::date($item['created_at'] ?? null),
            verified: in_array(Values::text($item['verified'] ?? null), ['buyer', 'verified', 'true', '1'], true),
            published: (bool) $published,
            photos: Values::urls($photos),
        );
    }
}
