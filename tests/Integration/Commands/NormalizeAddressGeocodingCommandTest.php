<?php

declare(strict_types=1);

use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Models\Address;
use Capell\Address\Tests\Fixtures\FakeAvailableAddressGeocodingProvider;

it('normalizes address geocoding from the console', function (): void {
    app()->bind(
        'address.geocoding.fixture.available',
        fn (): AddressGeocodingProvider => new FakeAvailableAddressGeocodingProvider,
    );
    app()->tag(['address.geocoding.fixture.available'], AddressGeocodingProvider::TAG);

    $address = Address::factory()->create([
        'line1' => '10 Downing Street',
        'meta' => [],
    ]);
    Address::factory()->create([
        'line1' => '11 Downing Street',
        'meta' => [],
    ]);

    $this->artisan('capell:address-geocode-normalize', [
        '--provider' => 'fixture-geocoding',
        '--dry-run' => true,
        '--limit' => 1,
    ])
        ->expectsOutputToContain('Geocoding dry run found 1 of 1 scanned address')
        ->assertSuccessful();

    expect($address->refresh()->meta)->toBe([]);

    $this->artisan('capell:address-geocode-normalize', [
        '--provider' => 'fixture-geocoding',
        '--limit' => 1,
    ])
        ->expectsOutputToContain('Normalized geocoding for 1 of 1 scanned address')
        ->assertSuccessful();

    expect($address->refresh()->meta)
        ->toMatchArray([
            'latitude' => '51.5074',
            'longitude' => '-0.1278',
            'geocoding_provider' => 'fixture-geocoding',
        ]);
});
