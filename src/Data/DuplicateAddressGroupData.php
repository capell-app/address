<?php

declare(strict_types=1);

namespace Capell\Address\Data;

use Spatie\LaravelData\Data;

final class DuplicateAddressGroupData extends Data
{
    /**
     * @param  list<int>  $addressIds
     */
    public function __construct(
        public readonly string $key,
        public readonly ?int $countryId,
        public readonly string $postalCode,
        public readonly string $line1,
        public readonly ?string $line2,
        public readonly int $count,
        public readonly array $addressIds,
    ) {}
}
