<?php

namespace NineteenNinetyFour\Reviews\Core\Import;

use DateTimeImmutable;
use Throwable;

/**
 * Reading the values export files hold, which are rarely tidy.
 */
final class Values
{
    public static function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));

        return $text === '' ? null : $text;
    }

    /**
     * A rating out of five: whole numbers 1 to 5, a 0–10 or 0–100 scale
     * brought down to five, or null.
     */
    public static function rating(mixed $value, int $scale = 5): ?int
    {
        if (is_string($value)) {
            $value = trim(str_replace(',', '.', $value));
        }

        if (! is_numeric($value)) {
            return null;
        }

        $value = (float) $value;

        if ($scale !== 5 && $scale > 0) {
            $value = $value / $scale * 5;
        }

        $rounded = (int) round($value);

        return $rounded >= 1 && $rounded <= 5 ? $rounded : null;
    }

    public static function date(mixed $value): ?DateTimeImmutable
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value) && strlen($value) >= 9)) {
            return (new DateTimeImmutable)->setTimestamp((int) $value);
        }

        $text = self::text($value);

        if ($text === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($text);
        } catch (Throwable) {
            return null;
        }
    }

    public static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(mb_strtolower((string) self::text($value)), ['1', 'true', 'yes', 'y', 'verified', 'verified buyer', 'verified purchase', 'published', 'approved'], true);
    }

    public static function email(mixed $value): ?string
    {
        $text = self::text($value);

        return $text !== null && filter_var($text, FILTER_VALIDATE_EMAIL) !== false ? mb_strtolower($text) : null;
    }

    /**
     * Photo addresses, from a list, or text separated by commas, spaces or
     * new lines.
     *
     * @return array<int, string>
     */
    public static function urls(mixed $value): array
    {
        $items = is_array($value) ? $value : (preg_split('/[\s,]+/', (string) self::text($value)) ?: []);

        return array_values(array_filter(array_map(
            fn ($url) => is_string($url) && preg_match('~^https?://~i', trim($url)) ? trim($url) : null,
            $items,
        )));
    }
}
