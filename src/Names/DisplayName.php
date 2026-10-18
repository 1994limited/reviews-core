<?php

namespace NineteenNinetyFour\Reviews\Core\Names;

/**
 * How a reviewer's name is shown: in full, or short (the first name and the
 * last name's initial, "Jo B.").
 */
final class DisplayName
{
    public const FULL = 'full';

    public const SHORT = 'short';

    public static function format(string $name, string $mode = self::SHORT): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        if ($name === '' || $mode !== self::SHORT) {
            return $name;
        }

        $parts = explode(' ', $name);

        if (count($parts) === 1) {
            return $name;
        }

        $last = mb_substr((string) end($parts), 0, 1);

        return $parts[0].' '.mb_strtoupper($last).'.';
    }

    /**
     * The first name alone, for "Hello Jo,".
     */
    public static function first(string $name): ?string
    {
        $first = explode(' ', trim($name))[0];

        return $first === '' ? null : $first;
    }
}
