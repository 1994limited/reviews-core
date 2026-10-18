<?php

namespace NineteenNinetyFour\Reviews\Core\Spam;

/**
 * The time trap's token: when the form was drawn, signed so it can't be made
 * up. People take more than a few seconds to write a review; a form sent
 * sooner is a bot's. On a statically cached page the form was drawn long
 * ago, so the form's script also sends how long the page has been open.
 */
final class TimeToken
{
    public function __construct(private readonly string $key) {}

    public function issue(?int $time = null): string
    {
        $time ??= time();

        return $time.'.'.$this->sign((string) $time);
    }

    /**
     * Whether a submission is too quick to be a person's: the token missing,
     * tampered with, or too recent, or the page open for too short a time.
     */
    public function tooQuick(mixed $token, mixed $secondsOpen, int $minimum, ?int $now = null): bool
    {
        if ($minimum <= 0) {
            return false;
        }

        if (is_string($secondsOpen) && ctype_digit($secondsOpen) && (int) $secondsOpen < $minimum) {
            return true;
        }

        if (! is_string($token) || ! preg_match('/^(\d{1,12})\.([a-f0-9]{64})$/', $token, $match)) {
            return true;
        }

        if (! hash_equals($this->sign($match[1]), $match[2])) {
            return true;
        }

        return ($now ?? time()) - (int) $match[1] < $minimum;
    }

    private function sign(string $value): string
    {
        return hash_hmac('sha256', 'reviews-time-trap|'.$value, $this->key);
    }
}
