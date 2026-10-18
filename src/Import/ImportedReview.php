<?php

namespace NineteenNinetyFour\Reviews\Core\Import;

use DateTimeImmutable;

/**
 * A review read from another platform, by an import or a sync, before a CMS
 * matches it to an entry and saves it. Everything a reviewer wrote is kept
 * as they wrote it.
 */
final class ImportedReview
{
    /**
     * @param  array<string, string>  $product  What the review is of, as the platform names it: any of sku, handle, id, url, name, gtin.
     * @param  array<int, string>  $photos  Addresses of the reviewer's photos.
     */
    public function __construct(
        public readonly string $source,
        public readonly ?string $sourceId,
        public readonly array $product,
        public readonly int $rating,
        public readonly ?string $title = null,
        public readonly ?string $body = null,
        public readonly string $name = '',
        public readonly ?string $email = null,
        public readonly ?DateTimeImmutable $date = null,
        public readonly bool $verified = false,
        public readonly ?string $reply = null,
        public readonly ?DateTimeImmutable $replyDate = null,
        public readonly bool $published = true,
        public readonly array $photos = [],
        public readonly ?string $url = null,
    ) {}

    /**
     * A review about the business as a whole, rather than a product.
     */
    public function isAboutTheBusiness(): bool
    {
        return $this->product === [];
    }

    /**
     * What tells this review apart from every other, so importing the same
     * file twice, or syncing again, doesn't add it twice: the platform's own
     * ID where there is one, or else what the review says.
     */
    public function fingerprint(): string
    {
        if ($this->sourceId !== null && $this->sourceId !== '') {
            return hash('sha256', $this->source.'|id|'.$this->sourceId);
        }

        $product = $this->product;
        ksort($product);

        return hash('sha256', implode('|', [
            $this->source,
            json_encode($product),
            mb_strtolower(trim($this->name)),
            $this->date?->format('Y-m-d'),
            $this->rating,
            mb_strtolower(trim((string) $this->title)),
            mb_strtolower(trim((string) $this->body)),
        ]));
    }
}
