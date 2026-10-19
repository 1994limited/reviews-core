<?php

namespace NineteenNinetyFour\Reviews\Core\Tests\Captcha;

use InvalidArgumentException;
use NineteenNinetyFour\Reviews\Core\Assets;
use NineteenNinetyFour\Reviews\Core\Captcha\Captchas;
use NineteenNinetyFour\Reviews\Core\Captcha\HCaptcha;
use NineteenNinetyFour\Reviews\Core\Captcha\RecaptchaV3;
use NineteenNinetyFour\Reviews\Core\Tests\FakeClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    public static function drivers(): array
    {
        return [
            ['turnstile', 'cf-turnstile-response', 'challenges.cloudflare.com/turnstile/v0/siteverify', 'class="cf-turnstile"'],
            ['hcaptcha', 'h-captcha-response', 'api.hcaptcha.com/siteverify', 'class="h-captcha"'],
            ['recaptcha', 'g-recaptcha-response', 'www.google.com/recaptcha/api/siteverify', 'class="g-recaptcha"'],
        ];
    }

    #[Test]
    #[DataProvider('drivers')]
    public function each_captcha_draws_its_widget_and_checks_the_token(string $driver, string $field, string $endpoint, string $widget): void
    {
        $http = new FakeClient([['success' => true], ['success' => false], null]);
        $captcha = Captchas::make($driver, 'site"key', 'secret', $http);

        $this->assertSame($field, $captcha->field());
        $this->assertStringContainsString($widget, $captcha->widget());
        $this->assertStringContainsString('data-sitekey="site&quot;key"', $captcha->widget());
        $this->assertStringContainsString('data-reviews-captcha', $captcha->widget());

        $this->assertTrue($captcha->verify('good', '203.0.113.9'));
        $this->assertFalse($captcha->verify('bad'));
        $this->assertFalse($captcha->verify('provider down'));
        $this->assertFalse($captcha->verify(''));
        $this->assertCount(3, $http->requests);

        $this->assertStringContainsString($endpoint, $http->requests[0]['url']);
        $this->assertSame('secret', $http->requests[0]['data']['secret']);
        $this->assertSame('good', $http->requests[0]['data']['response']);
        $this->assertSame('203.0.113.9', $http->requests[0]['data']['remoteip']);
    }

    #[Test]
    public function hcaptcha_also_sends_the_site_key(): void
    {
        $http = new FakeClient([['success' => true]]);
        (new HCaptcha('site', 'secret', $http))->verify('token');

        $this->assertSame('site', $http->requests[0]['data']['sitekey']);
    }

    #[Test]
    public function recaptcha_v3_needs_the_score_and_action(): void
    {
        $http = new FakeClient([
            ['success' => true, 'score' => 0.9, 'action' => 'review'],
            ['success' => true, 'score' => 0.6, 'action' => 'review'],
            ['success' => true, 'score' => 0.9, 'action' => 'login'],
            ['success' => true, 'action' => 'review'],
        ]);
        $captcha = new RecaptchaV3('site', 'secret', $http, ['score' => 0.7]);

        $this->assertStringContainsString('api.js?render=site', $captcha->widget());
        $this->assertStringContainsString('grecaptcha.execute', $captcha->widget());

        // Served as a file instead: no inline script, the key on the field.
        $file = new RecaptchaV3('site', 'secret', new FakeClient, ['inline_script' => false]);
        $this->assertStringNotContainsString('<script>', $file->widget());
        $this->assertStringContainsString('data-reviews-recaptcha="site"', $file->widget());
        $this->assertFileExists(Assets::scriptPath());
        $this->assertStringContainsString('data-reviews-recaptcha', (string) file_get_contents(Assets::scriptPath()));
        $this->assertFileExists(Assets::stylesheetPath());
        $this->assertTrue($captcha->verify('a'));
        $this->assertFalse($captcha->verify('b'));
        $this->assertFalse($captcha->verify('c'));
        $this->assertFalse($captcha->verify('d'));
    }

    #[Test]
    public function without_both_keys_there_is_no_captcha_and_unknown_drivers_are_refused(): void
    {
        $this->assertNull(Captchas::make('turnstile', 'site', '', new FakeClient));
        $this->assertTrue(Captchas::builtIn('hcaptcha'));
        $this->assertFalse(Captchas::builtIn('App\\MyCaptcha'));

        $this->expectException(InvalidArgumentException::class);
        Captchas::make('nope', 'a', 'b', new FakeClient);
    }
}
