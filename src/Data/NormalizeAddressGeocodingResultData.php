<?php

declare(strict_types=1);

namespace Capell\Address\Data;

final readonly class NormalizeAddressGeocodingResultData
{
    /**
     * @param  list<string>  $messages
     */
    public function __construct(
        public int|string|null $addressId,
        public bool $updated,
        public ?string $provider = null,
        public ?string $latitude = null,
        public ?string $longitude = null,
        public array $messages = [],
    ) {}
}
