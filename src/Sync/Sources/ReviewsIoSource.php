<?php

namespace NineteenNinetyFour\Reviews\Core\Sync\Sources;

use DateTimeImmutable;
use Generator;
use NineteenNinetyFour\Reviews\Core\Http\Client;
use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;
use NineteenNinetyFour\Reviews\Core\Import\Values;
use NineteenNinetyFour\Reviews\Core\Sync\Attribution;

/**
 * REVIEWS.io: company ("store") and product reviews, from one endpoint.
 */
class ReviewsIoSource extends PagedSource
{
    public const URL = 'https://api.reviews.io/reviews';

    public function key(): string
    {
        return 'reviewsio';
    }

    public function label(): string
    {
        return 'REVIEWS.io';
    }

    public function fields(): array
    {
        return [
            'store' => self::field(['label' => 'Store ID', 'help' => 'Your REVIEWS.io store ID (Integrations → API).']),
            'api_key' => self::field(['label' => 'API key', 'help' => 'Your private API key. Optional: it adds reviewers’ emails.'], secret: true, required: false),
        ];
    }

    public function attribution(): ?Attribution
    {
        return new Attribution('REVIEWS.io', 'https://www.reviews.io');
    }

    public function fetch(Client $http, array $config, ?DateTimeImmutable $since = null): Generator
    {
        $store = $this->setting($config, 'store');
        $key = $this->setting($config, 'api_key');

        foreach (['store_review', 'product_review'] as $type) {
            for ($page = 1; $page <= self::MAX_PAGES; $page++) {
                $query = array_filter([
                    'store' => $store,
                    'type' => $type,
                    'page' => $page,
                    'per_page' => 100,
                    'apikey' => $key ?: null,
                    'dateFrom' => $since?->format('Y-m-d'),
                ], fn ($value) => $value !== null);

                $reply = $this->get($http, self::URL, $query);
                $items = is_array($reply['reviews'] ?? null) ? $reply['reviews'] : [];

                foreach ($items as $item) {
                    if (is_array($item) && ($review = $this->review($item, $type)) !== null) {
                        yield $review;
                    }
                }

                $total = (int) ($reply['results_count'] ?? $reply['total'] ?? 0);

                if ($items === [] || $page * 100 >= $total) {
                    break;
                }
            }
        }
    }

    /**
     * @param  array<mixed>  $item
     */
    protected function review(array $item, string $type): ?ImportedReview
    {
        $rating = Values::rating($item['rating'] ?? null);

        if ($rating === null) {
            return null;
        }

        $reply = null;

        foreach (is_array($item['replies'] ?? null) ? $item['replies'] : [] as $answer) {
            $reply ??= is_array($answer) ? Values::text($answer['comments'] ?? $answer['reply'] ?? null) : Values::text($answer);
        }

        return new ImportedReview(
            source: $this->key(),
            sourceId: Values::text($item['store_review_id'] ?? $item['product_review_id'] ?? $item['id'] ?? null),
            product: $type === 'product_review' ? array_filter(['sku' => Values::text($item['sku'] ?? null)]) : [],
            rating: $rating,
            title: Values::text($item['title'] ?? null),
            body: Values::text($item['comments'] ?? $item['review'] ?? null),
            name: (string) Values::text(self::dig($item, 'author.name') ?? $item['reviewer']['first_name'] ?? null),
            email: Values::email(self::dig($item, 'author.email')),
            date: Values::date($item['date_created'] ?? $item['date'] ?? null),
            verified: Values::bool($item['verified_buyer'] ?? $item['verified'] ?? false),
            reply: $reply,
            photos: Values::urls($item['photos'] ?? $item['images'] ?? []),
        );
    }
}
