<?php

namespace NineteenNinetyFour\Reviews\Core\Http;

/**
 * The little HTTP the core needs (captcha checks, review platforms' APIs),
 * done by the CMS's own client.
 */
interface Client
{
    /**
     * Post a form; the decoded JSON reply, or null when it fails.
     *
     * @param  array<string, scalar|null>  $form
     * @return array<mixed>|null
     */
    public function postForm(string $url, array $form): ?array;

    /**
     * Get JSON; the decoded reply, or null when it fails.
     *
     * @param  array<string, scalar|null>  $query
     * @param  array<string, string>  $headers
     * @return array<mixed>|null
     */
    public function getJson(string $url, array $query = [], array $headers = []): ?array;
}
