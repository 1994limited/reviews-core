<?php

namespace NineteenNinetyFour\Reviews\Core\Captcha;

/**
 * Google reCAPTCHA v3: nothing to tick. Google scores each visit from 0 (a
 * bot) to 1 (a person); reviews scoring under the threshold (the "score"
 * option, 0.5) are refused.
 */
class RecaptchaV3 extends Recaptcha
{
    public const ACTION = 'review';

    /**
     * The script that asks Google for a token as the form is sent: in
     * resources/js/reviews.js, which a site serves as a file. Until it does
     * (the "inline_script" option, true by default), it's written inline
     * too, as before 1.0.1.
     */
    public function widget(): string
    {
        $key = $this->attr($this->siteKey);
        $html = '<input type="hidden" name="g-recaptcha-response" value="" data-reviews-captcha data-reviews-recaptcha="'.$key.'" data-reviews-action="'.self::ACTION.'">'
            .'<script src="https://www.google.com/recaptcha/api.js?render='.$key.'" async defer></script>';

        if (($this->options['inline_script'] ?? true) === false) {
            return $html;
        }

        $json = json_encode($this->siteKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return $html.'<script>(function(){document.querySelectorAll("[data-reviews-recaptcha]").forEach(function(input){var form=input.form;if(!form||form.dataset.reviewsRecaptcha)return;form.dataset.reviewsRecaptcha=1;form.addEventListener("submit",function(event){if(input.value||!window.grecaptcha)return;event.preventDefault();grecaptcha.ready(function(){grecaptcha.execute('.$json.',{action:"'.self::ACTION.'"}).then(function(token){input.value=token;form.requestSubmit?form.requestSubmit():form.submit()})})})})})();</script>';
    }

    protected function passed(array $result): bool
    {
        $threshold = is_numeric($this->options['score'] ?? null) ? (float) $this->options['score'] : 0.5;

        return parent::passed($result)
            && ($result['action'] ?? null) === self::ACTION
            && is_numeric($result['score'] ?? null)
            && (float) $result['score'] >= $threshold;
    }
}
