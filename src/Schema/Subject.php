<?php

namespace NineteenNinetyFour\Reviews\Core\Schema;

/**
 * What the reviews are of, as review schema describes it.
 */
final class Subject
{
    public function __construct(
        public readonly string $name,
        public readonly string $type = 'Thing',
        public readonly ?string $url = null,
        public readonly ?string $id = null,
    ) {}

    /**
     * A schema.org type name, or null for anything that isn't one.
     */
    public static function type(?string $type): ?string
    {
        return $type !== null && preg_match('/^[A-Za-z]{2,60}$/', $type) ? $type : null;
    }
}
