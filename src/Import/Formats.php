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
    /** Platforms' names, for "From Judge.me" beside a review. */
    public const PLATFORMS = [
        'feefo' => 'Feefo',
        'judgeme' => 'Judge.me',
        'reviewsio' => 'REVIEWS.io',
        'shopify' => 'Shopify',
        'trustpilot' => 'Trustpilot',
        'woocommerce' => 'WooCommerce',
        'yotpo' => 'Yotpo',
    ];

    /**
     * The name to show beside a review from a platform. Null for Reviews'
     * own export (a review moved from another site of yours) and for a
     * spreadsheet, which could have come from anywhere.
     */
    public static function platformName(?string $key): ?string
    {
        return $key !== null ? (self::PLATFORMS[$key] ?? null) : null;
    }

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
