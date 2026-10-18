<?php

namespace NineteenNinetyFour\Reviews\Core\Import;

use NineteenNinetyFour\Reviews\Core\Import\Formats\GenericFormat;
use NineteenNinetyFour\Reviews\Core\Import\Formats\ReviewsFormat;

/**
 * The formats Reviews reads.
 */
final class Formats
{
    /**
     * @return array<int, Format>
     */
    public static function all(): array
    {
        return [
            new ReviewsFormat,
            new GenericFormat,
        ];
    }
}
