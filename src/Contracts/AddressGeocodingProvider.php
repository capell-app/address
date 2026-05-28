<?php

declare(strict_types=1);

namespace Capell\Address\Contracts;

use Capell\Address\Data\AddressGeocodingResultData;
use Capell\Address\Models\Address;

interface AddressGeocodingProvider
{
    public const string TAG = 'capell-address:geocoding-providers';

    public function key(): string;

    public function isAvailable(): bool;

    public function geocode(Address $address): AddressGeocodingResultData;
}
