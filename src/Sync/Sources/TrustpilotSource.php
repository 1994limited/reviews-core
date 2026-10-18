<?php

namespace NineteenNinetyFour\Reviews\Core\Sync\Sources;

use DateTimeImmutable;
use Generator;
use NineteenNinetyFour\Reviews\Core\Http\Client;
use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;
use NineteenNinetyFour\Reviews\Core\Import\Values;
use NineteenNinetyFour\Reviews\Core\Sync\Attribution;

/**
 * Trustpilot (needs Trustpilot's paid API module). Its brand guidelines ask
 * for the Trustpilot logo and a link to your profile wherever its reviews
 * show, and that they're kept up to date (a regular sync does that); its
 * reviews never go in your own schema.
 */
class TrustpilotSource extends PagedSource
{
    public const BASE = 'https://api.trustpilot.com/v1';

    public function key(): string
    {
        return 'trustpilot';
    }

    public function label(): string
    {
        return 'Trustpilot';
    }

    public function fields(): array
    {
        return [
            'business_unit' => self::field(['label' => 'Business unit ID', 'help' => 'Trustpilot Business → Integrations → API.']),
            'api_key' => self::field(['label' => 'API key', 'help' => 'Needs Trustpilot’s API module.'], secret: true),
            'profile_url' => self::field(['label' => 'Profile address', 'help' => 'Your Trustpilot page, linked from your reviews as Trustpilot asks.']),
            'skus' => self::field(['label' => 'Product SKUs', 'help' => 'For product reviews: the SKUs to fetch, separated by commas. Leave empty for company reviews only.'], required: false),
        ];
    }

    public function attribution(): ?Attribution
    {
        return new Attribution('Trustpilot', 'https://www.trustpilot.com', 'Show the Trustpilot logo and link to your Trustpilot profile wherever these reviews appear.');
    }

    public function fetch(Client $http, array $config, ?DateTimeImmutable $since = null): Generator
    {
        $unit = rawurlencode($this->setting($config, 'business_unit'));
        $key = $this->setting($config, 'api_key');

        for ($page = 1; ; $page++) {
            $reply = $this->get($http, self::BASE.'/business-units/'.$unit.'/reviews', ['apikey' => $key, 'page' => $page, 'perPage' => 100, 'orderBy' => 'createdat.desc']);
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
                if (is_array($item) && ($review = $this->review($item, [])) !== null) {
                    yield $review;
                }
            }

            if (count($items) < 100) {
                break;
            }
        }

        $skus = array_filter(array_map('trim', explode(',', (string) ($config['skus'] ?? ''))));

        foreach ($skus as $sku) {
            for ($page = 1; ; $page++) {
                $reply = $this->get($http, self::BASE.'/product-reviews/business-units/'.$unit.'/reviews', ['apikey' => $key, 'sku' => $sku, 'page' => $page, 'perPage' => 100]);
                $items = is_array($reply['productReviews'] ?? null) ? $reply['productReviews'] : (is_array($reply['reviews'] ?? null) ? $reply['reviews'] : []);

                // One page past the limit: anything on it means there's more than
                // can be fetched.
                if ($page > static::MAX_PAGES) {
                    if ($items !== []) {
                        $this->tooManyPages();
                    }

                    break;
                }

                foreach ($items as $item) {
                    if (is_array($item) && ($review = $this->review($item, ['sku' => $sku])) !== null) {
                        yield $review;
                    }
                }

                if (count($items) < 100) {
                    break;
                }
            }
        }
    }

    /**
     * @param  array<mixed>  $item
     * @param  array<string, string>  $product
     */
    protected function review(array $item, array $product): ?ImportedReview
    {
        $rating = Values::rating($item['stars'] ?? null);

        if ($rating === null) {
            return null;
        }

        $reply = self::dig($item, 'companyReply.text') ?? self::dig($item, 'firstCompanyComment.comment');

        return new ImportedReview(
            source: $this->key(),
            sourceId: Values::text($item['id'] ?? null),
            product: $product,
            rating: $rating,
            title: Values::text($item['title'] ?? null),
            body: Values::text($item['text'] ?? $item['content'] ?? null),
            name: (string) Values::text(self::dig($item, 'consumer.displayName')),
            date: Values::date($item['createdAt'] ?? null),
            verified: Values::bool($item['isVerified'] ?? false),
            reply: Values::text($reply),
            replyDate: Values::date(self::dig($item, 'companyReply.createdAt')),
            photos: Values::urls(array_map(fn ($a) => is_array($a) ? ($a['url'] ?? self::dig($a, 'processedFiles.0.url')) : $a, is_array($item['attachments'] ?? null) ? $item['attachments'] : [])),
        );
    }
}
