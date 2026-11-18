<?php

declare(strict_types=1);

use Capell\Address\Actions\BuildGeocodingHttpClientAction;
use Capell\Address\Actions\NormalizeAddressGeocodingAction;
use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Models\Address;
use Capell\Address\Tests\Fixtures\HttpAddressGeocodingProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function registerHttpGeocodingProvider(string $key): void
{
    $binding = 'address.geocoding.fixture.' . $key;
    app()->bind($binding, fn (): AddressGeocodingProvider => new HttpAddressGeocodingProvider($key));
    app()->tag([$binding], AddressGeocodingProvider::TAG);
}

it('defaults to a bounded connect and total timeout with no retries', function (): void {
    $options = BuildGeocodingHttpClientAction::run()->getOptions();

    expect($options['timeout'])->toBe(5.0)
        ->and($options['connect_timeout'])->toBe(3.0);
});

it('reads the configured timeouts and clamps invalid values', function (): void {
    config([
        'capell-address.geocoding.timeout' => 8,
        'capell-address.geocoding.connect_timeout' => 2,
    ]);

    $options = BuildGeocodingHttpClientAction::run()->getOptions();
    expect($options['timeout'])->toBe(8.0)->and($options['connect_timeout'])->toBe(2.0);

    config(['capell-address.geocoding.timeout' => 9999, 'capell-address.geocoding.connect_timeout' => 9999]);
    $options = BuildGeocodingHttpClientAction::run()->getOptions();
    expect($options['timeout'])->toBe(30.0)->and($options['connect_timeout'])->toBe(30.0);

    config(['capell-address.geocoding.timeout' => 'soon', 'capell-address.geocoding.connect_timeout' => -1]);
    $options = BuildGeocodingHttpClientAction::run()->getOptions();
    expect($options['timeout'])->toBe(5.0)->and($options['connect_timeout'])->toBe(3.0);
});

it('never lets the connect timeout exceed the total timeout', function (): void {
    config(['capell-address.geocoding.timeout' => 2, 'capell-address.geocoding.connect_timeout' => 10]);

    expect(BuildGeocodingHttpClientAction::run()->getOptions()['connect_timeout'])->toBe(2.0);
});

it('sends the geocoding request with the configured timeouts and stores the result', function (): void {
    config(['capell-address.geocoding.timeout' => 7, 'capell-address.geocoding.connect_timeout' => 2]);
    $seen = [];
    Http::fake(function (Request $request, array $options) use (&$seen) {
        $seen = $options;

        return Http::response(['lat' => '51.5', 'lon' => '-0.12']);
    });
    registerHttpGeocodingProvider('timeouts');
    $address = Address::factory()->create(['meta' => []]);

    $result = NormalizeAddressGeocodingAction::run($address);

    expect($seen['timeout'])->toBe(7.0)
        ->and($seen['connect_timeout'])->toBe(2.0)
        ->and($result->updated)->toBeTrue()
        ->and($address->refresh()->meta['latitude'])->toBe('51.5');
});

it('treats a provider timeout as a geocoding miss and leaves the address unchanged', function (): void {
    Http::fake(function (): never {
        throw new ConnectionException('cURL error 28: Operation timed out after 5001 milliseconds');
    });
    registerHttpGeocodingProvider('slow');
    $address = Address::factory()->create(['meta' => ['note' => 'kept']]);

    $result = NormalizeAddressGeocodingAction::run($address);

    expect($result->updated)->toBeFalse()
        ->and($result->latitude)->toBeNull()
        ->and($address->refresh()->meta)->toBe(['note' => 'kept']);
});

it('falls through to the next provider after a timeout', function (): void {
    Http::fake([
        'geocoder.test/*' => function (Request $request) {
            static $calls = 0;
            $calls++;

            if ($calls === 1) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response(['lat' => '48.85', 'lon' => '2.35']);
        },
    ]);
    registerHttpGeocodingProvider('slow-first');
    registerHttpGeocodingProvider('fast-second');
    $address = Address::factory()->create(['meta' => []]);

    $result = NormalizeAddressGeocodingAction::run($address);

    expect($result->updated)->toBeTrue()
        ->and($result->provider)->toBe('fast-second');
});

it('keeps the console batch running when a provider times out', function (): void {
    Http::fake(function (): never {
        throw new ConnectionException('cURL error 28: Operation timed out');
    });
    registerHttpGeocodingProvider('batch');
    Address::factory()->count(2)->create(['meta' => []]);

    $this->artisan('capell:address-geocode-normalize', ['--provider' => 'batch'])
        ->expectsOutputToContain(__('capell-address::geocoding.completed', ['updated' => 0, 'scanned' => 2]))
        ->assertSuccessful();
});
