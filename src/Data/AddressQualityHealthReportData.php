<?php

declare(strict_types=1);

namespace Capell\Address\Data;

use Spatie\LaravelData\Data;

final class AddressQualityHealthReportData extends Data
{
    /**
     * @param  list<string>  $validationProviders
     * @param  list<string>  $geocodingProviders
     * @param  list<string>  $issues
     * @param  list<DuplicateAddressGroupData>  $duplicateGroups
     */
    public function __construct(
        public readonly string $status,
        public readonly int $checkedAddresses,
        public readonly int $missingCountries,
        public readonly int $missingCoordinates,
        public readonly int $invalidCoordinates,
        public readonly array $validationProviders,
        public readonly array $geocodingProviders,
        public readonly array $issues,
        public readonly int $duplicateAddresses = 0,
        public readonly array $duplicateGroups = [],
    ) {}
}
