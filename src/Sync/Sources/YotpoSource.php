<?php

namespace NineteenNinetyFour\Reviews\Core\Sync\Sources;

use DateTimeImmutable;
use Generator;
use NineteenNinetyFour\Reviews\Core\Http\Client;
use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;
use NineteenNinetyFour\Reviews\Core\Import\Values;
use NineteenNinetyFour\Reviews\Core\Sync\Attribution;
use NineteenNinetyFour\Reviews\Core\Sync\SyncException;

/**
 * Yotpo. The app key and secret are swapped for a token first. Only reviews
 * changed since the last sync are asked for. Site reviews (Yotpo's
 * "yotpo_site_reviews" product) are reviews of the business.
 */
class YotpoSource extends PagedSource
{
    public const TOKEN_URL = 'https://api.yotpo.com/oauth/token';

    public const URL = 'https://api.yotpo.com/v1/apps/%s/reviews';

    public function key(): string
    {
        return 'yotpo';
    }

    public function label(): string
    {
        return 'Yotpo';
    }

    public function fields(): array
    {
        return [
            'app_key' => self::field(['label' => 'App key', 'help' => 'Yotpo → Settings → Store settings.']),
            'secret' => self::field(['label' => 'Secret key', 'help' => 'From the same page.'], secret: true),
        ];
    }

    public function attribution(): ?Attribution
    {
        return new Attribution('Yotpo', 'https://www.yotpo.com');
    }

    public function fetch(Client $http, array $config, ?DateTimeImmutable $since = null): Generator
    {
        $app = $this->setting($config, 'app_key');
        $secret = $this->setting($config, 'secret');

        $token = $http->postForm(self::TOKEN_URL, ['client_id' => $app, 'client_secret' => $secret, 'grant_type' => 'client_credentials']);
        $token = is_array($token) ? Values::text($token['access_token'] ?? null) : null;

        if ($token === null) {
            throw new SyncException('Yotpo refused the app key and secret key.');
        }

        for ($page = 1; ; $page++) {
            $query = array_filter(['count' => 100, 'page' => $page, 'since_updated_at' => $since?->format(DATE_ATOM)], fn ($value) => $value !== null);
            $reply = $this->get($http, sprintf(self::URL, rawurlencode($app)), $query, ['X-Yotpo-Token' => $token]);
            $items = is_array($reply['reviews'] ?? null) ? $reply['reviews'] : [];

            // One page past the limit: anything on it means there's more than
            // can be fetched.
            if ($page > static::MAX_PAGES) {
                if ($items !== []) {
                    $this->tooManyPages();
                }

                break;
            }

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
        $rating = Values::rating($item['score'] ?? null);

        if ($rating === null) {
            return null;
        }

        $sku = Values::text($item['sku'] ?? null);

        return new ImportedReview(
            source: $this->key(),
            sourceId: Values::text($item['id'] ?? null),
            product: $sku === null || $sku === 'yotpo_site_reviews' ? [] : ['sku' => $sku],
            rating: $rating,
            title: Values::text($item['title'] ?? null),
            body: Values::text($item['content'] ?? null),
            name: (string) Values::text($item['name'] ?? null),
            email: Values::email($item['email'] ?? null),
            date: Values::date($item['created_at'] ?? null),
            verified: Values::text($item['reviewer_type'] ?? null) === 'verified_buyer',
            published: ! Values::bool($item['deleted'] ?? false),
        );
    }
}
