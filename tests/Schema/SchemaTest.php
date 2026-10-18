<?php

namespace NineteenNinetyFour\Reviews\Core\Tests\Schema;

use NineteenNinetyFour\Reviews\Core\Rating\Summary;
use NineteenNinetyFour\Reviews\Core\Schema\SchemaBuilder;
use NineteenNinetyFour\Reviews\Core\Schema\SchemaReview;
use NineteenNinetyFour\Reviews\Core\Schema\Subject;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    #[Test]
    public function schema_has_the_subject_average_and_reviews(): void
    {
        $data = SchemaBuilder::build(
            new Subject('The Long Field', 'Book', 'https://example.com/books/the-long-field', 'https://example.com/books/the-long-field#book'),
            new Summary([5 => 1, 4 => 1]),
            [new SchemaReview(5, 'Jo B.', '2026-05-01', 'Lovely', 'Loved it </script><script>alert(1)</script>'), new SchemaReview(4, 'Ana L.')],
        );

        $this->assertSame('Book', $data['@type']);
        $this->assertSame('https://example.com/books/the-long-field#book', $data['@id']);
        $this->assertEquals(4.5, $data['aggregateRating']['ratingValue']);
        $this->assertSame(2, $data['aggregateRating']['reviewCount']);
        $this->assertCount(2, $data['review']);
        $this->assertArrayNotHasKey('name', $data['review'][1]);

        $script = SchemaBuilder::script($data);
        $this->assertSame(1, substr_count($script, '</script>'));
        $this->assertStringNotContainsString('<script>alert', $script);
    }

    #[Test]
    public function there_is_no_schema_without_reviews_and_bad_types_become_thing(): void
    {
        $this->assertNull(SchemaBuilder::build(new Subject('X'), new Summary));
        $this->assertSame('', SchemaBuilder::script(null));
        $this->assertSame('', SchemaBuilder::properties(null));
        $this->assertSame('Thing', SchemaBuilder::build(new Subject('X', 'Bad Type!'), new Summary([3 => 1]))['@type']);
    }

    #[Test]
    public function properties_keep_json_valid_with_reviews_or_without(): void
    {
        $wrap = fn (string $properties) => json_decode('{ '.$properties.' "@type": "Product" }', true);

        $this->assertSame(['@type' => 'Product'], $wrap(SchemaBuilder::properties(SchemaBuilder::parts(new Summary))));

        $with = $wrap(SchemaBuilder::properties(SchemaBuilder::parts(new Summary([4 => 1]), [new SchemaReview(4, 'Jo')])));
        $this->assertSame(['aggregateRating', 'review', '@type'], array_keys($with));
    }
}
