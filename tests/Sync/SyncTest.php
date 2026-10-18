<?php

namespace NineteenNinetyFour\Reviews\Core\Tests\Sync;

use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;
use NineteenNinetyFour\Reviews\Core\Sync\Source;
use NineteenNinetyFour\Reviews\Core\Sync\Sources;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\FeefoSource;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\JudgeMeSource;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\ReviewsIoSource;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\TrustpilotSource;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\YotpoSource;
use NineteenNinetyFour\Reviews\Core\Sync\SyncException;
use NineteenNinetyFour\Reviews\Core\Tests\FakeClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SyncTest extends TestCase
{
    /**
     * @param  array<string, string>  $config
     * @return array<int, ImportedReview>
     */
    private function fetch(Source $source, FakeClient $http, array $config, ?\DateTimeImmutable $since = null): array
    {
        return iterator_to_array($source->fetch($http, $config, $since), false);
    }

    #[Test]
    public function the_registry_lists_every_source_by_key(): void
    {
        $this->assertSame(['feefo', 'reviewsio', 'judgeme', 'yotpo', 'trustpilot'], array_keys(Sources::all()));
        $this->assertInstanceOf(YotpoSource::class, Sources::find('yotpo'));
        $this->assertNull(Sources::find('google'));
    }

    #[Test]
    public function a_missing_required_setting_is_explained(): void
    {
        $this->expectException(SyncException::class);
        $this->expectExceptionMessage('Judge.me needs its private api token.');

        $this->fetch(new JudgeMeSource, new FakeClient, ['shop_domain' => 'x.myshopify.com']);
    }

    #[Test]
    public function no_answer_is_an_error_not_an_empty_sync(): void
    {
        $this->expectException(SyncException::class);

        $this->fetch(new FeefoSource, new FakeClient([null]), ['merchant' => 'dog-ear']);
    }

    #[Test]
    public function feefo_splits_service_and_product_ratings_and_pages(): void
    {
        $http = new FakeClient([
            ['summary' => ['meta' => ['pages' => 2]], 'reviews' => [[
                'url' => 'https://www.feefo.com/reviews/dog-ear/1',
                'customer' => ['display_name' => 'Ada L'],
                'service' => ['id' => 's1', 'rating' => ['rating' => 5], 'title' => 'Quick', 'review' => 'Fast delivery', 'created_at' => '2026-09-01T10:00:00Z'],
                'products' => [[
                    'id' => 'p1', 'rating' => ['rating' => 4], 'review' => 'Lovely book',
                    'product' => ['sku' => 'BK-1', 'title' => 'The Book'],
                    'media' => [['url' => 'https://img.feefo.com/1.jpg']],
                    'thread' => [['merchant_comment' => 'Thank you!']],
                ]],
            ]]],
            ['summary' => ['meta' => ['pages' => 2]], 'reviews' => [[
                'customer' => ['display_name' => 'Bo'],
                'products' => [['id' => 'p2', 'rating' => ['rating' => 9]]],
            ]]],
        ]);

        $reviews = $this->fetch(new FeefoSource, $http, ['merchant' => 'dog-ear']);

        $this->assertCount(2, $reviews);
        $this->assertTrue($reviews[0]->isAboutTheBusiness());
        $this->assertSame('s1', $reviews[0]->sourceId);
        $this->assertSame(['sku' => 'BK-1', 'name' => 'The Book'], $reviews[1]->product);
        $this->assertSame('Thank you!', $reviews[1]->reply);
        $this->assertSame(['https://img.feefo.com/1.jpg'], $reviews[1]->photos);
        $this->assertSame(2, $http->requests[1]['data']['page']);
        $this->assertTrue((new FeefoSource)->schemaAllowed());
    }

    #[Test]
    public function reviews_io_fetches_store_then_product_reviews(): void
    {
        $since = new \DateTimeImmutable('2026-10-01');
        $http = new FakeClient([
            ['results_count' => 1, 'reviews' => [['store_review_id' => 10, 'rating' => 5, 'comments' => 'Great shop', 'author' => ['name' => 'Cy'], 'date_created' => '2026-10-02 09:00:00', 'replies' => [['comments' => 'Thanks']]]]],
            ['results_count' => 1, 'reviews' => [['product_review_id' => 20, 'rating' => 3, 'sku' => 'BK-2', 'review' => 'Okay', 'reviewer' => ['first_name' => 'Di'], 'verified_buyer' => 'yes']]],
        ]);

        $reviews = $this->fetch(new ReviewsIoSource, $http, ['store' => 'dog-ear'], $since);

        $this->assertCount(2, $reviews);
        $this->assertSame([], $reviews[0]->product);
        $this->assertSame('Thanks', $reviews[0]->reply);
        $this->assertSame(['sku' => 'BK-2'], $reviews[1]->product);
        $this->assertSame('Di', $reviews[1]->name);
        $this->assertTrue($reviews[1]->verified);
        $this->assertSame('2026-10-01', $http->requests[0]['data']['dateFrom']);
        $this->assertArrayNotHasKey('apikey', $http->requests[0]['data']);
        $this->assertSame('product_review', $http->requests[1]['data']['type']);
    }

    #[Test]
    public function judge_me_matches_products_by_handle_and_respects_hidden(): void
    {
        $page = array_fill(0, 100, ['id' => 1, 'rating' => 5, 'product_handle' => 'the-book', 'reviewer' => ['name' => 'Ed']]);
        $http = new FakeClient([
            ['reviews' => $page],
            ['reviews' => [['id' => 2, 'rating' => 2, 'product_handle' => 'other', 'product_external_id' => 99, 'hidden' => true, 'verified' => 'buyer', 'reviewer' => ['name' => 'Fi', 'email' => 'fi@example.com'], 'pictures' => [['urls' => ['original' => 'https://judge.me/p.jpg']]]]]],
        ]);

        $reviews = $this->fetch(new JudgeMeSource, $http, ['shop_domain' => 'x.myshopify.com', 'api_token' => 't']);

        $this->assertCount(101, $reviews);
        $last = $reviews[100];
        $this->assertSame(['handle' => 'other', 'id' => '99'], $last->product);
        $this->assertFalse($last->published);
        $this->assertTrue($last->verified);
        $this->assertSame('fi@example.com', $last->email);
        $this->assertSame(['https://judge.me/p.jpg'], $last->photos);
        $this->assertCount(2, $http->requests);
    }

    #[Test]
    public function yotpo_swaps_keys_for_a_token_and_asks_for_changes_only(): void
    {
        $http = new FakeClient([
            ['access_token' => 'tok'],
            ['reviews' => [
                ['id' => 1, 'score' => 5, 'sku' => 'yotpo_site_reviews', 'content' => 'Nice shop', 'name' => 'Gus'],
                ['id' => 2, 'score' => 4, 'sku' => 'BK-3', 'reviewer_type' => 'verified_buyer', 'deleted' => true],
            ]],
        ]);

        $reviews = $this->fetch(new YotpoSource, $http, ['app_key' => 'app', 'secret' => 's'], new \DateTimeImmutable('2026-10-01T00:00:00+00:00'));

        $this->assertSame('POST', $http->requests[0]['method']);
        $this->assertSame(['X-Yotpo-Token' => 'tok'], $http->requests[1]['headers']);
        $this->assertSame('2026-10-01T00:00:00+00:00', $http->requests[1]['data']['since_updated_at']);
        $this->assertStringContainsString('/apps/app/reviews', $http->requests[1]['url']);
        $this->assertTrue($reviews[0]->isAboutTheBusiness());
        $this->assertSame(['sku' => 'BK-3'], $reviews[1]->product);
        $this->assertTrue($reviews[1]->verified);
        $this->assertFalse($reviews[1]->published);
    }

    #[Test]
    public function yotpo_refusing_the_keys_is_an_error(): void
    {
        $this->expectException(SyncException::class);

        $this->fetch(new YotpoSource, new FakeClient([['error' => 'invalid_client']]), ['app_key' => 'app', 'secret' => 's']);
    }

    #[Test]
    public function trustpilot_company_and_product_reviews_carry_attribution(): void
    {
        $http = new FakeClient([
            ['reviews' => [['id' => 'tp1', 'stars' => 4, 'title' => 'Good', 'text' => 'Fine', 'consumer' => ['displayName' => 'Hal'], 'isVerified' => true, 'companyReply' => ['text' => 'Cheers', 'createdAt' => '2026-10-03T00:00:00Z']]]],
            ['productReviews' => [['id' => 'tp2', 'stars' => 5, 'content' => 'Loved it', 'consumer' => ['displayName' => 'Ivy']]]],
        ]);

        $source = new TrustpilotSource;
        $reviews = $this->fetch($source, $http, ['business_unit' => 'bu', 'api_key' => 'k', 'profile_url' => 'https://uk.trustpilot.com/review/example.com', 'skus' => ' BK-4 , ']);

        $this->assertCount(2, $reviews);
        $this->assertSame('Cheers', $reviews[0]->reply);
        $this->assertTrue($reviews[0]->verified);
        $this->assertSame(['sku' => 'BK-4'], $reviews[1]->product);
        $this->assertSame('Loved it', $reviews[1]->body);
        $this->assertSame('BK-4', $http->requests[1]['data']['sku']);
        $this->assertFalse($source->schemaAllowed());
        $this->assertSame('Trustpilot', $source->attribution()?->name);
    }

    #[Test]
    public function every_source_describes_its_settings(): void
    {
        foreach (Sources::all() as $source) {
            $this->assertNotSame([], $source->fields(), $source->key());

            foreach ($source->fields() as $field) {
                $this->assertNotSame('', $field['label']);
                $this->assertIsBool($field['secret']);
            }
        }
    }

    #[Test]
    public function running_out_of_pages_is_an_error_not_a_complete_sync(): void
    {
        $source = new class extends JudgeMeSource
        {
            public const MAX_PAGES = 2;
        };
        $page = ['reviews' => array_fill(0, 100, ['id' => 1, 'rating' => 5, 'product_handle' => 'x'])];

        $this->expectException(SyncException::class);
        $this->expectExceptionMessage('more than 2 pages');

        $this->fetch($source, new FakeClient([$page, $page, $page]), ['shop_domain' => 'x', 'api_token' => 't']);
    }

    #[Test]
    public function exactly_the_page_limit_is_a_complete_sync(): void
    {
        $source = new class extends JudgeMeSource
        {
            public const MAX_PAGES = 2;
        };
        $page = ['reviews' => array_fill(0, 100, ['id' => 1, 'rating' => 5, 'product_handle' => 'x'])];

        $reviews = $this->fetch($source, new FakeClient([$page, $page, ['reviews' => []]]), ['shop_domain' => 'x', 'api_token' => 't']);

        $this->assertCount(200, $reviews);
    }
}
