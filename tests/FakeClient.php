<?php

namespace NineteenNinetyFour\Reviews\Core\Tests;

use NineteenNinetyFour\Reviews\Core\Http\Client;

/**
 * Answers requests from a list, and remembers them.
 */
final class FakeClient implements Client
{
    /** @var array<int, array{method: string, url: string, data: array<mixed>, headers: array<string, string>}> */
    public array $requests = [];

    /**
     * @param  array<int, array<mixed>|null>  $replies
     */
    public function __construct(private array $replies = []) {}

    public function postForm(string $url, array $form): ?array
    {
        $this->requests[] = ['method' => 'POST', 'url' => $url, 'data' => $form, 'headers' => []];

        return array_shift($this->replies);
    }

    public function getJson(string $url, array $query = [], array $headers = []): ?array
    {
        $this->requests[] = ['method' => 'GET', 'url' => $url, 'data' => $query, 'headers' => $headers];

        return array_shift($this->replies);
    }
}
