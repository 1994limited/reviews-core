<?php

namespace NineteenNinetyFour\Reviews\Core;

/**
 * The files a site serves for the ready-made views and review forms.
 */
final class Assets
{
    /** The default styles. */
    public static function stylesheetPath(): string
    {
        return dirname(__DIR__).'/resources/css/reviews.css';
    }

    /**
     * What review forms need in the browser: the time check, and the token
     * for reCAPTCHA v3 (when its "inline_script" option is false).
     */
    public static function scriptPath(): string
    {
        return dirname(__DIR__).'/resources/js/reviews.js';
    }
}
