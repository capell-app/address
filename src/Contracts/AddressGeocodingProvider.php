<?php

declare(strict_types=1);

namespace Capell\Address\Contracts;

use Capell\Address\Data\AddressGeocodingResultData;
use Capell\Address\Models\Address;

/**
 * Resolves coordinates for a stored address.
 *
 * An implementation that calls an external service sends address text to a
 * third party. Its documentation must state what it sends, where it sends it
 * and how long the third party keeps it. The site operator decides and records
 * the lawful basis before enabling the provider. Capell ships no provider and
 * chooses no basis.
 */
interface AddressGeocodingProvider
{
    public const string TAG = 'capell-address:geocoding-providers';

    public function key(): string;

    public function isAvailable(): bool;

    public function geocode(Address $address): AddressGeocodingResultData;
}
