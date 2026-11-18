<?php

declare(strict_types=1);

namespace Capell\Address\Tests\Fixtures;

use Capell\Address\Actions\BuildGeocodingHttpClientAction;
use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Data\AddressGeocodingResultData;
use Capell\Address\Models\Address;
use Illuminate\Support\Arr;
use UnexpectedValueException;

/**
 * Reference provider that makes its outbound request through the bounded client.
 */
final class HttpAddressGeocodingProvider implements AddressGeocodingProvider
{
    public function __construct(private readonly string $key = 'fixture-http-geocoding') {}

    public function key(): string
    {
        return $this->key;
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function geocode(Address $address): AddressGeocodingResultData
    {
        $response = BuildGeocodingHttpClientAction::run()
            ->get('https://geocoder.test/search', ['q' => $address->line1])
            ->throw();
        $body = $response->json();
        throw_unless(is_array($body), UnexpectedValueException::class, 'Unexpected geocoder response.');

        return new AddressGeocodingResultData(
            provider: $this->key(),
            latitude: Arr::string($body, 'lat'),
            longitude: Arr::string($body, 'lon'),
        );
    }
}
