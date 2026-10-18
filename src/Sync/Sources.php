<?php

namespace NineteenNinetyFour\Reviews\Core\Sync;

use NineteenNinetyFour\Reviews\Core\Sync\Sources\FeefoSource;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\JudgeMeSource;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\ReviewsIoSource;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\TrustpilotSource;
use NineteenNinetyFour\Reviews\Core\Sync\Sources\YotpoSource;

/**
 * The platforms Reviews syncs from. Google isn't one: its Business Profile
 * API terms don't allow keeping its reviews for more than 30 days.
 */
final class Sources
{
    /**
     * @return array<string, Source>
     */
    public static function all(): array
    {
        $sources = [];

        foreach ([new FeefoSource, new ReviewsIoSource, new JudgeMeSource, new YotpoSource, new TrustpilotSource] as $source) {
            $sources[$source->key()] = $source;
        }

        return $sources;
    }

    public static function find(string $key): ?Source
    {
        return self::all()[$key] ?? null;
    }
}
