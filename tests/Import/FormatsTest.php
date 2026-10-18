<?php

namespace NineteenNinetyFour\Reviews\Core\Tests\Import;

use NineteenNinetyFour\Reviews\Core\Import\Importer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FormatsTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function headers(): array
    {
        return [
            'yotpo' => ['yotpo', "Review ID,Review Creation Date,Review Status,Review Score,Review Title,Review Content,Published Image URL,Comment Content,Comment Date,Reviewer Display Name,Reviewer Email,Reviewer Type,Product ID,Product URL,Product Handle,Product SKU\n"],
            'judgeme' => ['judgeme', "title,body,rating,review_date,reviewer_name,reviewer_email,product_url,picture_urls,product_id,product_handle,reply,reply_date,ip_address,curated\n"],
            'reviewsio' => ['reviewsio', "Date,Name,Email Address,Order ID,Star Rating,Comment,SKU,Images,Review Title,Replies\n"],
            'shopify' => ['shopify', "product_handle,state,rating,title,author,email,location,body,reply,created_at,replied_at\n"],
            'trustpilot' => ['trustpilot', "Review Id,Review Created (UTC),Review Stars,Review Title,Review Content,Consumer Name,Reply Content\n"],
            'woocommerce' => ['woocommerce', "comment_ID,comment_post_ID,comment_author,comment_author_email,comment_date,comment_content,comment_approved,rating,verified\n"],
            'reviews' => ['reviews', "id,entry,entry_title,rating,title,body,name,email,status,verified,created_at\n"],
        ];
    }

    #[Test]
    #[DataProvider('headers')]
    public function each_platform_s_export_is_recognised(string $key, string $header): void
    {
        $this->assertSame($key, (new Importer)->read($header)->format?->key());
    }

    #[Test]
    public function yotpo_site_reviews_are_about_the_business(): void
    {
        $csv = "Review ID,Review Creation Date,Review Status,Review Score,Review Title,Review Content,Published Image URL,Reviewer Display Name,Reviewer Type,Product SKU\n"
            ."1,2026-01-02,Published,5,Great,Nice shop,,Ann,verified_buyer,yotpo_site_reviews\n"
            ."2,2026-01-03,Pending,4,Good,Nice book,https://cdn.yotpo.com/a.jpg,Ben,anonymous_user,BK-1\n";

        $reviews = (new Importer)->read($csv)->reviews;

        $this->assertTrue($reviews[0]->isAboutTheBusiness());
        $this->assertTrue($reviews[0]->verified);
        $this->assertTrue($reviews[0]->published);
        $this->assertSame(['sku' => 'BK-1'], $reviews[1]->product);
        $this->assertFalse($reviews[1]->verified);
        $this->assertFalse($reviews[1]->published);
        $this->assertSame(['https://cdn.yotpo.com/a.jpg'], $reviews[1]->photos);
    }

    #[Test]
    public function judge_me_spam_isnt_published(): void
    {
        $csv = "title,body,rating,review_date,reviewer_name,product_handle,curated\n"
            ."Hi,Fine,4,2026-02-01,Cat,the-book,ok\n"
            ."Buy,Cheap pills,5,2026-02-02,Spam Bot,the-book,spam\n";

        $reviews = (new Importer)->read($csv)->reviews;

        $this->assertSame(['handle' => 'the-book'], $reviews[0]->product);
        $this->assertTrue($reviews[0]->published);
        $this->assertFalse($reviews[1]->published);
    }

    #[Test]
    public function woocommerce_keeps_only_approved_comments_published(): void
    {
        $csv = "comment_ID,comment_post_ID,comment_author,comment_content,comment_approved,rating\n"
            ."7,42,Dee,Great,1,5\n"
            ."8,42,Eve,Waiting,0,3\n";

        $reviews = (new Importer)->read($csv)->reviews;

        $this->assertSame(['id' => '42'], $reviews[0]->product);
        $this->assertSame('7', $reviews[0]->sourceId);
        $this->assertTrue($reviews[0]->published);
        $this->assertFalse($reviews[1]->published);
    }
}
