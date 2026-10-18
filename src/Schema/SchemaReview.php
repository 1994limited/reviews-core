<?php

namespace NineteenNinetyFour\Reviews\Core\Schema;

/**
 * One review, as review schema needs it.
 */
final class SchemaReview
{
    public function __construct(
        public readonly int $rating,
        public readonly string $author,
        public readonly ?string $date = null,
        public readonly ?string $title = null,
        public readonly ?string $body = null,
    ) {}
}
