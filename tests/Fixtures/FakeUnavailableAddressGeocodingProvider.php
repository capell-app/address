<?php

declare(strict_types=1);

namespace Capell\Address\Tests\Fixtures;

use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Data\AddressGeocodingResultData;
use Capell\Address\Models\Address;

final class FakeUnavailableAddressGeocodingProvider implements AddressGeocodingProvider
{
    public function key(): string
    {
        return 'unavailable-geocoding';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function geocode(Address $address): AddressGeocodingResultData
    {
        return new AddressGeocodingResultData(
            provider: $this->key(),
            messages: ['Provider is unavailable.'],
        );
    }
}
