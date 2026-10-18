<?php

namespace NineteenNinetyFour\Reviews\Core\Tests\Spam;

use NineteenNinetyFour\Reviews\Core\Spam\Links;
use NineteenNinetyFour\Reviews\Core\Spam\TimeToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SpamTest extends TestCase
{
    #[Test]
    public function links_are_counted_across_texts(): void
    {
        $this->assertSame(3, Links::count('See https://a.example and www.b.example', 'buy at cheap.com'));
        $this->assertSame(0, Links::count(null, '', 'A lovely book. 5/5.'));
    }

    #[Test]
    public function the_time_trap_catches_quick_or_forged_forms(): void
    {
        $tokens = new TimeToken('secret');
        $now = 1_800_000_000;

        $this->assertFalse($tokens->tooQuick($tokens->issue($now - 60), null, 3, $now));
        $this->assertTrue($tokens->tooQuick($tokens->issue($now - 1), null, 3, $now));
        $this->assertTrue($tokens->tooQuick(null, null, 3, $now));
        $this->assertTrue($tokens->tooQuick(($now - 60).'.'.str_repeat('a', 64), null, 3, $now));
        $this->assertTrue($tokens->tooQuick((new TimeToken('other'))->issue($now - 60), null, 3, $now));
        // A cached page drawn long ago, but opened a second ago.
        $this->assertTrue($tokens->tooQuick($tokens->issue($now - 600), '1', 3, $now));
        $this->assertFalse($tokens->tooQuick($tokens->issue($now - 600), '30', 3, $now));
        // Turned off.
        $this->assertFalse($tokens->tooQuick(null, null, 0, $now));
    }
}
