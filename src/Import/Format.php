<?php

namespace NineteenNinetyFour\Reviews\Core\Import;

/**
 * One platform's export, read into ImportedReviews.
 */
interface Format
{
    /** A short name, such as "judgeme". */
    public function key(): string;

    /** What people call it, such as "Judge.me (CSV export)". */
    public function label(): string;

    /**
     * How sure it is that a file with these columns is its kind: 0 for not
     * at all. The highest wins.
     *
     * @param  array<int, string>  $columns  The file's columns, as Csv::key() makes them.
     */
    public function score(array $columns): int;

    /**
     * One row of the file as a review, or null when it isn't one (no
     * rating, say).
     *
     * @param  array<string, mixed>  $row  The row, keyed by Csv::key() of each column.
     */
    public function map(array $row): ?ImportedReview;
}
