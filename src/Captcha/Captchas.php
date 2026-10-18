<?php

namespace NineteenNinetyFour\Reviews\Core\Captcha;

use InvalidArgumentException;
use NineteenNinetyFour\Reviews\Core\Http\Client;

/**
 * The captchas that come with Reviews, by name.
 */
final class Captchas
{
    public const DRIVERS = [
        'turnstile' => Turnstile::class,
        'hcaptcha' => HCaptcha::class,
        'recaptcha' => Recaptcha::class,
        'recaptcha_v3' => RecaptchaV3::class,
    ];

    /**
     * One of the built-in captchas, or null without both keys (no captcha).
     *
     * @param  array<string, mixed>  $options
     */
    public static function make(string $driver, ?string $siteKey, ?string $secretKey, Client $http, array $options = []): ?Captcha
    {
        $class = self::DRIVERS[$driver] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unknown reviews captcha [{$driver}]. Use turnstile, hcaptcha, recaptcha or recaptcha_v3.");
        }

        if ($siteKey === null || $siteKey === '' || $secretKey === null || $secretKey === '') {
            return null;
        }

        return new $class($siteKey, $secretKey, $http, $options);
    }

    public static function builtIn(string $driver): bool
    {
        return isset(self::DRIVERS[$driver]);
    }
}
