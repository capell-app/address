<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Contracts\AddressValidationProvider;
use Capell\Address\Data\AddressQualityHealthReportData;
use Capell\Address\Models\Address;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static AddressQualityHealthReportData run(iterable<int, Address>|null $addresses = null)
 */
final class BuildAddressQualityHealthReportAction
{
    use AsAction;

    /**
     * @param  iterable<int, Address>|null  $addresses
     */
    public function handle(?iterable $addresses = null): AddressQualityHealthReportData
    {
        $addresses = $this->addresses($addresses);
        $issues = [];
        $missingCountries = 0;
        $missingCoordinates = 0;
        $invalidCoordinates = 0;

        foreach ($addresses as $address) {
            if ($this->countryIsMissing($address)) {
                $missingCountries++;
                $issues[] = sprintf('Address %s is missing an enabled country.', $this->addressLabel($address));
            }

            $coordinates = $this->coordinates($address);

            if ($coordinates === null) {
                $missingCoordinates++;

                continue;
            }

            if (! $this->coordinatesAreValid($coordinates['latitude'], $coordinates['longitude'])) {
                $invalidCoordinates++;
                $issues[] = sprintf('Address %s has invalid latitude or longitude metadata.', $this->addressLabel($address));
            }
        }

        $validationProviders = $this->availableProviderKeys(AddressValidationProvider::TAG);
        $geocodingProviders = $this->availableProviderKeys(AddressGeocodingProvider::TAG);

        if ($validationProviders === []) {
            $issues[] = 'No available address validation provider is registered.';
        }

        if ($geocodingProviders === []) {
            $issues[] = 'No available address geocoding provider is registered.';
        }

        return new AddressQualityHealthReportData(
            status: $this->status($invalidCoordinates, $missingCountries, $issues),
            checkedAddresses: $addresses->count(),
            missingCountries: $missingCountries,
            missingCoordinates: $missingCoordinates,
            invalidCoordinates: $invalidCoordinates,
            validationProviders: $validationProviders,
            geocodingProviders: $geocodingProviders,
            issues: array_values(array_unique($issues)),
        );
    }

    /**
     * @param  iterable<int, Address>|null  $addresses
     * @return EloquentCollection<int, Address>
     */
    private function addresses(?iterable $addresses): EloquentCollection
    {
        if ($addresses === null) {
            return Address::query()
                ->with('country')
                ->get();
        }

        if ($addresses instanceof EloquentCollection) {
            $addresses->loadMissing('country');

            return $addresses;
        }

        $addressCollection = collect($addresses)
            ->each(fn (Address $address): bool => $address->loadMissing('country') instanceof Address)
            ->values();

        return new EloquentCollection($addressCollection->all());
    }

    private function countryIsMissing(Address $address): bool
    {
        return $address->country_id === null
            || $address->country === null
            || $address->country->status === false;
    }

    /**
     * @return array{latitude: string, longitude: string}|null
     */
    private function coordinates(Address $address): ?array
    {
        $latitude = data_get($address->meta, 'latitude');
        $longitude = data_get($address->meta, 'longitude');

        if (! is_scalar($latitude) || ! is_scalar($longitude) || trim((string) $latitude) === '' || trim((string) $longitude) === '') {
            return null;
        }

        return [
            'latitude' => trim((string) $latitude),
            'longitude' => trim((string) $longitude),
        ];
    }

    private function coordinatesAreValid(string $latitude, string $longitude): bool
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return false;
        }

        $latitudeValue = (float) $latitude;
        $longitudeValue = (float) $longitude;

        return $latitudeValue >= -90.0
            && $latitudeValue <= 90.0
            && $longitudeValue >= -180.0
            && $longitudeValue <= 180.0;
    }

    /**
     * @return list<string>
     */
    private function availableProviderKeys(string $tag): array
    {
        $keys = [];

        foreach (app()->tagged($tag) as $provider) {
            if (! is_object($provider)) {
                continue;
            }

            if (! method_exists($provider, 'isAvailable')) {
                continue;
            }

            if (! $provider->isAvailable()) {
                continue;
            }

            $keys[] = method_exists($provider, 'key') ? (string) $provider->key() : $provider::class;
        }

        return $keys;
    }

    /**
     * @param  list<string>  $issues
     */
    private function status(int $invalidCoordinates, int $missingCountries, array $issues): string
    {
        if ($invalidCoordinates > 0 || $missingCountries > 0) {
            return 'failed';
        }

        return $issues === [] ? 'passed' : 'warning';
    }

    private function addressLabel(Address $address): string
    {
        if ($address->getKey() !== null) {
            return '#' . $address->getKey();
        }

        return trim($address->full_address) ?: 'unsaved';
    }
}
