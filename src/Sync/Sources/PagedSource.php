<?php

namespace NineteenNinetyFour\Reviews\Core\Sync\Sources;

use NineteenNinetyFour\Reviews\Core\Http\Client;
use NineteenNinetyFour\Reviews\Core\Sync\Attribution;
use NineteenNinetyFour\Reviews\Core\Sync\Source;
use NineteenNinetyFour\Reviews\Core\Sync\SyncException;

/**
 * Shared by the sources: settings, and paging with a ceiling, so a broken
 * API can never keep a sync going for ever.
 */
abstract class PagedSource implements Source
{
    /** The most pages one sync reads. */
    public const MAX_PAGES = 500;

    public function attribution(): ?Attribution
    {
        return null;
    }

    public function schemaAllowed(): bool
    {
        return false;
    }

    /**
     * The platform still had more after MAX_PAGES: stop, and say so, so the
     * sync isn't taken as complete.
     */
    protected function tooManyPages(): never
    {
        throw new SyncException(sprintf('%s has more than %d pages of reviews: only part of them were fetched. Try again, or import the rest from an export.', $this->label(), static::MAX_PAGES));
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function setting(array $config, string $key): string
    {
        $value = trim((string) ($config[$key] ?? ''));

        if ($value === '' && ($this->fields()[$key]['required'] ?? false)) {
            throw new SyncException(sprintf('%s needs its %s.', $this->label(), mb_strtolower($this->fields()[$key]['label'])));
        }

        return $value;
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @param  array<string, string>  $headers
     * @return array<mixed>
     */
    protected function get(Client $http, string $url, array $query, array $headers = []): array
    {
        $reply = $http->getJson($url, $query, $headers);

        if ($reply === null) {
            throw new SyncException(sprintf('%s didn’t answer, or refused the keys. Check them, and try again later.', $this->label()));
        }

        return $reply;
    }

    /**
     * @param  array<mixed>  $data
     */
    protected static function dig(array $data, string $path): mixed
    {
        foreach (explode('.', $path) as $key) {
            if (! is_array($data) || ! array_key_exists($key, $data)) {
                return null;
            }

            $data = $data[$key];
        }

        return $data;
    }

    /**
     * @param  array{label: string, help: string}  $field
     * @return array{label: string, help: string, secret: bool, required: bool}
     */
    protected static function field(array $field, bool $secret = false, bool $required = true): array
    {
        return $field + ['secret' => $secret, 'required' => $required];
    }
}
