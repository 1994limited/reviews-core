<?php

namespace NineteenNinetyFour\Reviews\Core\Tests\Names;

use NineteenNinetyFour\Reviews\Core\Names\DisplayName;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DisplayNameTest extends TestCase
{
    #[Test]
    public function names_are_shortened_or_shown_in_full(): void
    {
        $this->assertSame('Jo B.', DisplayName::format('Jo Anne  Bloggs'));
        $this->assertSame('Seán R.', DisplayName::format('Seán ó Ríordáin'));
        $this->assertSame('ana Ö.', DisplayName::format('ana ölund'));
        $this->assertSame('Cher', DisplayName::format('Cher'));
        $this->assertSame('Jo Anne Bloggs', DisplayName::format('Jo Anne Bloggs', DisplayName::FULL));
        $this->assertSame('Jo', DisplayName::first('Jo Bloggs'));
        $this->assertNull(DisplayName::first('  '));
    }
}
