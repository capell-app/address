<?php

declare(strict_types=1);

namespace Capell\Address\Data;

use Spatie\LaravelData\Data;

final class AddressGeocodingResultData extends Data
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        public readonly string $provider,
        public readonly ?string $latitude = null,
        public readonly ?string $longitude = null,
        public readonly ?float $confidence = null,
        public readonly array $messages = [],
    ) {}
}
