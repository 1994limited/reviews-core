<?php

namespace NineteenNinetyFour\Reviews\Core\Rating;

/**
 * Five stars for a rating, each full, half or empty. A half shows from a
 * quarter up, and a full star from three quarters.
 */
final class Stars
{
    /**
     * @return array<int, array{type: string, full: bool, half: bool, empty: bool, number: int}>
     */
    public static function for(float|int|null $rating): array
    {
        $rating = max(0.0, min(5.0, (float) $rating));
        $stars = [];

        for ($i = 1; $i <= 5; $i++) {
            $part = $rating - ($i - 1);
            $type = $part >= 0.75 ? 'full' : ($part >= 0.25 ? 'half' : 'empty');

            $stars[] = ['type' => $type, 'full' => $type === 'full', 'half' => $type === 'half', 'empty' => $type === 'empty', 'number' => $i];
        }

        return $stars;
    }

    /**
     * A rating from 1 to 5, or null for anything else.
     */
    public static function score(mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value != $value) {
            return null;
        }

        $value = (int) $value;

        return $value >= 1 && $value <= 5 ? $value : null;
    }
}
