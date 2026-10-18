<?php

namespace NineteenNinetyFour\Reviews\Core\Spam;

/**
 * How many links are in some text: addresses, "www." and bare domains on
 * the top-level domains spam favours.
 */
final class Links
{
    public static function count(?string ...$texts): int
    {
        $count = 0;

        foreach ($texts as $text) {
            if ($text !== null && $text !== '') {
                $count += (int) preg_match_all('~(https?://|www\.|\b[a-z0-9-]+\.(com|net|org|ru|xyz|top|info|io|co|biz)\b)~i', $text);
            }
        }

        return $count;
    }
}
