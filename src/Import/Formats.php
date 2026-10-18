<?php

namespace NineteenNinetyFour\Reviews\Core\Import;

use NineteenNinetyFour\Reviews\Core\Import\Formats\GenericFormat;
use NineteenNinetyFour\Reviews\Core\Import\Formats\JudgeMeFormat;
use NineteenNinetyFour\Reviews\Core\Import\Formats\ReviewsFormat;
use NineteenNinetyFour\Reviews\Core\Import\Formats\ReviewsIoFormat;
use NineteenNinetyFour\Reviews\Core\Import\Formats\ShopifyFormat;
use NineteenNinetyFour\Reviews\Core\Import\Formats\TrustpilotFormat;
use NineteenNinetyFour\Reviews\Core\Import\Formats\WooCommerceFormat;
use NineteenNinetyFour\Reviews\Core\Import\Formats\YotpoFormat;

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
            new YotpoFormat,
            new JudgeMeFormat,
            new ReviewsIoFormat,
            new ShopifyFormat,
            new TrustpilotFormat,
            new WooCommerceFormat,
            new GenericFormat,
        ];
    }
}
