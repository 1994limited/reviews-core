<?php

namespace NineteenNinetyFour\Reviews\Core\Sync;

use DateTimeImmutable;
use Generator;
use NineteenNinetyFour\Reviews\Core\Http\Client;
use NineteenNinetyFour\Reviews\Core\Import\ImportedReview;

/**
 * A review platform a shop's own reviews are synced from on a schedule.
 */
interface Source
{
    /** A short name, such as "feefo". */
    public function key(): string;

    public function label(): string;

    /**
     * The settings it needs, each with a label, help, and whether it's a
     * secret (kept in .env, never shown back).
     *
     * @return array<string, array{label: string, help: string, secret: bool, required: bool}>
     */
    public function fields(): array;

    /**
     * What has to be shown with its reviews, as the platform's terms ask
     * (its name and a link back), or null when nothing is.
     */
    public function attribution(): ?Attribution;

    /**
     * Whether its reviews may go in review schema. Only where the platform
     * says so, and only for product reviews: Google ignores (and may act
     * against) ratings aggregated from other websites.
     */
    public function schemaAllowed(): bool;

    /**
     * Its reviews, newest changes first where the API allows, changed
     * since the last sync when it can ask for that.
     *
     * @param  array<string, string>  $config
     * @return Generator<int, ImportedReview>
     *
     * @throws SyncException When the platform refuses or can't be reached.
     */
    public function fetch(Client $http, array $config, ?DateTimeImmutable $since = null): Generator;
}
