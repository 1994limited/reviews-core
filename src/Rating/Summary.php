<?php

namespace NineteenNinetyFour\Reviews\Core\Rating;

/**
 * Approved reviews in numbers: how many gave each rating, the average, and
 * each criterion's average. Built from counts a CMS has already worked out,
 * so it never needs the reviews themselves.
 */
final class Summary
{
    /** @var array<int, int> */
    public readonly array $stars;

    /**
     * @param  array<int|string, int|string>  $stars  How many reviews gave each rating, by rating.
     * @param  array<string, array{sum: int|float, count: int}>  $criteria  Each criterion's total and count, by handle.
     */
    public function __construct(array $stars = [], public readonly array $criteria = [])
    {
        $counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        foreach ($stars as $rating => $count) {
            $rating = (int) $rating;

            if (isset($counts[$rating])) {
                $counts[$rating] += max(0, (int) $count);
            }
        }

        $this->stars = $counts;
    }

    /**
     * @param  array{stars?: array<int|string, int|string>, criteria?: array<string, array{sum: int|float, count: int}>}  $tally
     */
    public static function from(array $tally): self
    {
        return new self($tally['stars'] ?? [], $tally['criteria'] ?? []);
    }

    public function count(): int
    {
        return array_sum($this->stars);
    }

    /**
     * The exact average, or null with no reviews.
     */
    public function average(): ?float
    {
        $count = $this->count();

        if ($count === 0) {
            return null;
        }

        $total = 0;

        foreach ($this->stars as $rating => $times) {
            $total += $rating * $times;
        }

        return $total / $count;
    }

    /**
     * The average to one decimal place, as people read it.
     */
    public function rounded(): ?float
    {
        $average = $this->average();

        return $average === null ? null : round($average, 1);
    }

    /**
     * The average as a percentage of five stars (4.5 is 90).
     */
    public function percent(): float
    {
        $average = $this->average();

        return $average === null ? 0.0 : round($average / 5 * 100, 1);
    }

    /**
     * Each rating from 5 to 1, how many gave it, and their share as a whole
     * percentage.
     *
     * @return array<int, array{rating: int, total: int, percent: int}>
     */
    public function breakdown(): array
    {
        $count = $this->count();
        $rows = [];

        foreach ($this->stars as $rating => $total) {
            $rows[] = ['rating' => $rating, 'total' => $total, 'percent' => $count > 0 ? (int) round($total / $count * 100) : 0];
        }

        return $rows;
    }

    /**
     * A criterion's average, or null when nobody rated it.
     */
    public function criterionAverage(string $handle): ?float
    {
        $count = $this->criteria[$handle]['count'] ?? 0;

        return $count > 0 ? $this->criteria[$handle]['sum'] / $count : null;
    }

    /**
     * @return array{stars: array<int, int>, criteria: array<string, array{sum: int|float, count: int}>}
     */
    public function toArray(): array
    {
        return ['stars' => $this->stars, 'criteria' => $this->criteria];
    }
}
