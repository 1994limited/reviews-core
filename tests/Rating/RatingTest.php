<?php

namespace NineteenNinetyFour\Reviews\Core\Tests\Rating;

use NineteenNinetyFour\Reviews\Core\Rating\Stars;
use NineteenNinetyFour\Reviews\Core\Rating\Summary;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RatingTest extends TestCase
{
    #[Test]
    public function a_summary_works_out_the_average_and_breakdown(): void
    {
        $summary = new Summary(['5' => 2, 4 => 1, 1 => '1', 9 => 4], ['value' => ['sum' => 7, 'count' => 2]]);

        $this->assertSame(4, $summary->count());
        $this->assertEqualsWithDelta(3.75, $summary->average(), 0.0001);
        $this->assertSame(3.8, $summary->rounded());
        $this->assertSame(75.0, $summary->percent());
        $this->assertSame(['rating' => 5, 'total' => 2, 'percent' => 50], $summary->breakdown()[0]);
        $this->assertSame(['rating' => 1, 'total' => 1, 'percent' => 25], $summary->breakdown()[4]);
        $this->assertEqualsWithDelta(3.5, $summary->criterionAverage('value'), 0.0001);
        $this->assertNull($summary->criterionAverage('taste'));
    }

    #[Test]
    public function an_empty_summary_has_no_average(): void
    {
        $summary = Summary::from([]);

        $this->assertSame(0, $summary->count());
        $this->assertNull($summary->average());
        $this->assertSame(0.0, $summary->percent());
        $this->assertSame([5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0], $summary->toArray()['stars']);
    }

    #[Test]
    public function stars_are_full_half_or_empty(): void
    {
        $types = fn ($rating) => implode('', array_map(fn ($s) => $s['type'][0], Stars::for($rating)));

        $this->assertSame('fffhe', $types(3.5));
        $this->assertSame('fffff', $types(4.8));
        $this->assertSame('ffffe', $types(4.2));
        $this->assertSame('eeeee', $types(null));
        $this->assertSame('fffff', $types(9));
    }

    #[Test]
    public function a_score_is_a_whole_number_from_one_to_five(): void
    {
        $this->assertSame(4, Stars::score('4'));
        $this->assertNull(Stars::score('4.5'));
        $this->assertNull(Stars::score(0));
        $this->assertNull(Stars::score(6));
        $this->assertNull(Stars::score('five'));
    }
}
