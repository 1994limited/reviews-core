<?php

namespace NineteenNinetyFour\Reviews\Core\Import;

/**
 * What an import file held: its reviews, which format it was read as, and
 * the rows left out, with why.
 */
final class ImportResult
{
    /**
     * @param  array<int, ImportedReview>  $reviews
     * @param  array<int, array{row: int, reason: string}>  $skipped
     */
    public function __construct(
        public readonly ?Format $format,
        public readonly array $reviews,
        public readonly array $skipped = [],
    ) {}
}
