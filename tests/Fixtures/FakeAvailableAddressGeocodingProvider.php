<?php

declare(strict_types=1);

namespace Capell\Address\Tests\Fixtures;

use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Data\AddressGeocodingResultData;
use Capell\Address\Models\Address;

final class FakeAvailableAddressGeocodingProvider implements AddressGeocodingProvider
{
    public function key(): string
    {
        return 'fixture-geocoding';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function geocode(Address $address): AddressGeocodingResultData
    {
        return new AddressGeocodingResultData(
            provider: $this->key(),
            latitude: '51.5074',
            longitude: '-0.1278',
            confidence: 0.8,
            messages: [],
        );
    }
}
