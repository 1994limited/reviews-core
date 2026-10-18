<?php

namespace NineteenNinetyFour\Reviews\Core\Schema;

use NineteenNinetyFour\Reviews\Core\Rating\Summary;

/**
 * Review schema (schema.org JSON-LD): the subject, its average rating and
 * its latest reviews, for search engines' star ratings. Nothing a reviewer
 * wrote can break out of the script tag.
 */
final class SchemaBuilder
{
    /**
     * The JSON-LD, or null with no reviews (an empty rating isn't allowed).
     *
     * @param  array<int, SchemaReview>  $reviews
     * @return array<string, mixed>|null
     */
    public static function build(Subject $subject, Summary $summary, array $reviews = []): ?array
    {
        $parts = self::parts($summary, $reviews);

        if ($parts === null) {
            return null;
        }

        $data = ['@context' => 'https://schema.org', '@type' => Subject::type($subject->type) ?? 'Thing'];

        if ($subject->id !== null && $subject->id !== '') {
            $data['@id'] = $subject->id;
        }

        $data['name'] = $subject->name;

        if ($subject->url !== null && $subject->url !== '') {
            $data['url'] = $subject->url;
        }

        return $data + $parts;
    }

    /**
     * The rating and reviews alone, to add to schema printed by something
     * else (an SEO plugin, say). Null with no reviews.
     *
     * @param  array<int, SchemaReview>  $reviews
     * @return array{aggregateRating: array<string, mixed>, review?: array<int, array<string, mixed>>}|null
     */
    public static function parts(Summary $summary, array $reviews = []): ?array
    {
        if ($summary->count() === 0) {
            return null;
        }

        $parts = ['aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => round((float) $summary->average(), 2),
            'bestRating' => 5,
            'worstRating' => 1,
            'ratingCount' => $summary->count(),
            'reviewCount' => $summary->count(),
        ]];

        if ($reviews !== []) {
            $parts['review'] = array_values(array_map(fn (SchemaReview $review) => array_filter([
                '@type' => 'Review',
                'author' => ['@type' => 'Person', 'name' => $review->author],
                'datePublished' => $review->date,
                'name' => $review->title,
                'reviewBody' => $review->body,
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $review->rating, 'bestRating' => 5, 'worstRating' => 1],
            ], fn ($value) => $value !== null && $value !== ''), $reviews));
        }

        return $parts;
    }

    /**
     * JSON safe to print anywhere in a page, including inside a script tag.
     *
     * @param  array<mixed>  $data
     */
    public static function encode(array $data, bool $pretty = false): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | ($pretty ? JSON_PRETTY_PRINT : 0));
    }

    /**
     * The properties without their braces, each followed by a comma, to go
     * straight after the "{" of JSON-LD written elsewhere: valid JSON with
     * reviews or without (when it's empty).
     *
     * @param  array<string, mixed>|null  $parts
     */
    public static function properties(?array $parts): string
    {
        if ($parts === null) {
            return '';
        }

        $out = '';

        foreach ($parts as $key => $value) {
            $out .= substr(self::encode([$key => $value]), 1, -1).',';
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function script(?array $data): string
    {
        return $data === null ? '' : '<script type="application/ld+json">'.self::encode($data, true).'</script>';
    }
}
