<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Data\AddressGeocodingResultData;
use Capell\Address\Data\NormalizeAddressGeocodingResultData;
use Capell\Address\Models\Address;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * @method static NormalizeAddressGeocodingResultData run(Address $address, ?string $providerKey = null, bool $dryRun = false)
 */
final class NormalizeAddressGeocodingAction
{
    use AsAction;

    public function handle(Address $address, ?string $providerKey = null, bool $dryRun = false): NormalizeAddressGeocodingResultData
    {
        $addressId = $address->getKey();

        foreach ($this->providers($providerKey) as $provider) {
            $result = $provider->geocode($address);

            if (! $this->hasValidCoordinates($result)) {
                continue;
            }

            $meta = is_array($address->meta) ? $address->meta : [];
            $nextMeta = array_filter([
                ...$meta,
                'latitude' => $result->latitude,
                'longitude' => $result->longitude,
                'geocoding_provider' => $result->provider,
                'geocoding_confidence' => $result->confidence,
                'geocoded_at' => now()->toIso8601String(),
            ], static fn (mixed $value): bool => $value !== null);

            $updated = Arr::get($meta, 'latitude') !== $result->latitude
                || Arr::get($meta, 'longitude') !== $result->longitude
                || Arr::get($meta, 'geocoding_provider') !== $result->provider;

            if ($updated && ! $dryRun) {
                $address->forceFill(['meta' => $nextMeta])->save();
            }

            return new NormalizeAddressGeocodingResultData(
                addressId: is_int($addressId) || is_string($addressId) ? $addressId : null,
                updated: $updated,
                provider: $result->provider,
                latitude: $result->latitude,
                longitude: $result->longitude,
                messages: $result->messages,
            );
        }

        return new NormalizeAddressGeocodingResultData(
            addressId: is_int($addressId) || is_string($addressId) ? $addressId : null,
            updated: false,
            messages: ['No available geocoding provider returned valid coordinates.'],
        );
    }

    /**
     * @return list<AddressGeocodingProvider>
     */
    private function providers(?string $providerKey): array
    {
        $providers = [];

        foreach (app()->tagged(AddressGeocodingProvider::TAG) as $provider) {
            if (! $provider instanceof AddressGeocodingProvider || ! $provider->isAvailable()) {
                continue;
            }

            if (is_string($providerKey) && $providerKey !== '' && $provider->key() !== $providerKey) {
                continue;
            }

            $providers[] = $provider;
        }

        return $providers;
    }

    private function hasValidCoordinates(AddressGeocodingResultData $result): bool
    {
        if (! is_numeric($result->latitude) || ! is_numeric($result->longitude)) {
            return false;
        }

        $latitude = (float) $result->latitude;
        $longitude = (float) $result->longitude;

        return $latitude >= -90.0
            && $latitude <= 90.0
            && $longitude >= -180.0
            && $longitude <= 180.0;
    }
}
