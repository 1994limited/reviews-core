<?php

namespace NineteenNinetyFour\Reviews\Core\Captcha;

/**
 * A captcha on the review form.
 */
interface Captcha
{
    /**
     * The HTML that goes in the form: the widget and its script. It has
     * data-reviews-captcha on an element, so a form can tell it's there.
     */
    public function widget(): string;

    /**
     * Whether the visitor passed, from the token the widget put in the form.
     */
    public function verify(?string $token, ?string $ip = null): bool;

    /**
     * The form field the widget fills in.
     */
    public function field(): string;
}
