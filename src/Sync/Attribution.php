<?php

namespace NineteenNinetyFour\Reviews\Core\Sync;

/**
 * What a platform asks to be shown with its reviews.
 */
final class Attribution
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $url = null,
        public readonly ?string $note = null,
    ) {}
}
