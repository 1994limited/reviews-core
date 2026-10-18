<?php

namespace NineteenNinetyFour\Reviews\Core\Tests\Import;

use NineteenNinetyFour\Reviews\Core\Import\Csv;
use NineteenNinetyFour\Reviews\Core\Import\Importer;
use NineteenNinetyFour\Reviews\Core\Import\Values;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ImporterTest extends TestCase
{
    #[Test]
    public function a_spreadsheet_with_usual_columns_is_read(): void
    {
        $csv = "\xEF\xBB\xBFReview ID,Product SKU,Stars,Title,Review,Reviewer Name,Email,Created At,Verified Buyer,Reply\n"
            ."r1,BOOK-1,5,Lovely,\"Line one\nLine two, with a comma\",Jo Bloggs,JO@example.com,2026-05-01 10:00:00,yes,Thanks!\n"
            ."r2,BOOK-2,1,Bad,Arrived damaged,Ana Lee,,2026-05-02,no,\n"
            ."r3,BOOK-2,,No rating,Hmm,Sam,,2026-05-03,,\n";

        $result = (new Importer)->read($csv);

        $this->assertSame('generic', $result->format?->key());
        $this->assertCount(2, $result->reviews);
        $this->assertSame([['row' => 3, 'reason' => 'No rating from 1 to 5.']], $result->skipped);

        $first = $result->reviews[0];
        $this->assertSame('r1', $first->sourceId);
        $this->assertSame(['sku' => 'BOOK-1'], $first->product);
        $this->assertSame(5, $first->rating);
        $this->assertSame("Line one\nLine two, with a comma", $first->body);
        $this->assertSame('jo@example.com', $first->email);
        $this->assertSame('2026-05-01', $first->date?->format('Y-m-d'));
        $this->assertTrue($first->verified);
        $this->assertSame('Thanks!', $first->reply);

        // Every rating is kept: an import never picks and chooses.
        $this->assertSame(1, $result->reviews[1]->rating);
    }

    #[Test]
    public function semicolons_tabs_and_other_scales_are_read(): void
    {
        $result = (new Importer)->read("rating;body;sku\n4,0;Good;A\n");
        $this->assertSame(4, $result->reviews[0]->rating);

        $result = (new Importer)->read("score\tcomment\n");
        $this->assertSame([], $result->reviews);
    }

    #[Test]
    public function json_lists_and_nested_objects_are_read(): void
    {
        $json = json_encode(['reviews' => [
            ['id' => 7, 'rating' => 4, 'body' => 'Good', 'product' => ['sku' => 'BOOK-1', 'title' => 'The Long Field'], 'author' => 'Jo', 'photos' => ['https://cdn.example/a.jpg', 'not a url']],
        ]]);

        $review = (new Importer)->read($json)->reviews[0];

        $this->assertSame('7', $review->sourceId);
        $this->assertSame(['sku' => 'BOOK-1', 'name' => 'The Long Field'], $review->product);
        $this->assertSame('Jo', $review->name);
        $this->assertSame(['https://cdn.example/a.jpg'], $review->photos);
    }

    #[Test]
    public function reviews_own_export_is_recognised(): void
    {
        $json = json_encode(['email' => 'jo@example.com', 'reviews' => [
            ['id' => '01abc', 'entry' => 'book-1', 'entry_title' => 'The Long Field', 'rating' => 4, 'status' => 'approved', 'verified' => true, 'created_at' => '2026-05-01T10:00:00+00:00', 'name' => 'Jo', 'reply' => 'Thanks'],
            ['id' => '01abd', 'entry' => 'book-1', 'rating' => 2, 'status' => 'spam', 'created_at' => '2026-05-02T10:00:00+00:00', 'name' => 'Bot'],
        ]]);

        $result = (new Importer)->read($json);

        $this->assertSame('reviews', $result->format?->key());
        $this->assertSame(['id' => 'book-1', 'name' => 'The Long Field'], $result->reviews[0]->product);
        $this->assertTrue($result->reviews[0]->published);
        $this->assertFalse($result->reviews[1]->published);
    }

    #[Test]
    public function a_file_that_isnt_reviews_is_explained(): void
    {
        $result = (new Importer)->read("first_name,last_name\nJo,Bloggs\n");

        $this->assertNull($result->format);
        $this->assertSame([], $result->reviews);
        $this->assertStringContainsString('rating column', $result->skipped[0]['reason']);
    }

    #[Test]
    public function the_same_review_has_the_same_fingerprint(): void
    {
        $csv = "rating,body,name,date,sku\n5,Good,Jo,2026-05-01,A\n";

        $a = (new Importer)->read($csv)->reviews[0];
        $b = (new Importer)->read($csv)->reviews[0];
        $c = (new Importer)->read(str_replace('Good', 'Great', $csv))->reviews[0];

        $this->assertSame($a->fingerprint(), $b->fingerprint());
        $this->assertNotSame($a->fingerprint(), $c->fingerprint());
    }

    #[Test]
    public function only_plain_web_addresses_are_kept_as_links(): void
    {
        $this->assertSame('https://example.com/r/1', Values::url(' https://example.com/r/1 '));
        $this->assertSame('http://example.com', Values::url('http://example.com'));

        foreach (['javascript:alert(1)', 'JAVASCRIPT://example.com/%0Aalert(1)', 'data:text/html,hi', '//example.com', 'https://user:pass@example.com', "https://example.com/\nx", 'https://'] as $bad) {
            $this->assertNull(Values::url($bad), $bad);
        }

        $this->assertSame(['https://ok.example/a.jpg'], Values::urls(['javascript:x', 'https://ok.example/a.jpg']));
    }

    #[Test]
    public function exported_cells_can_t_run_as_formulas_and_read_back_as_they_were(): void
    {
        $csv = Csv::class;

        foreach (['=HYPERLINK("x")', '+1', '-2', '@SUM(A1)', "\tx", "\rx"] as $value) {
            $this->assertSame("'".$value, $csv::cell($value));
            $this->assertSame($value, $csv::uncell($csv::cell($value)));
        }

        $this->assertSame('Fine', $csv::cell('Fine'));
        $this->assertSame("'quoted", $csv::uncell("'quoted"));

        $file = "id,entry,entry_title,rating,title,body,name,email,status,verified,created_at,source_url\n"
            ."r1,book,Book,4,\"'=HYPERLINK(\"\"x\"\")\",Body,\"'-Dash\",,approved,0,2026-01-01,https://example.com/r/1\n";
        $review = (new Importer)->read($file)->reviews[0];

        $this->assertSame('=HYPERLINK("x")', $review->title);
        $this->assertSame('-Dash', $review->name);
        $this->assertSame('https://example.com/r/1', $review->url);
    }
}
