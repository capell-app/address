<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use RuntimeException;

/**
 * Seeds a handful of countries and addresses for disposable screenshot runs so
 * the Countries and Addresses admin tables are captured populated.
 */
final class SeedAddressScreenshotFixtureAction
{
    /**
     * @var list<array{iso2: string, iso3: string, name: string}>
     */
    private const array Countries = [
        ['iso2' => 'GB', 'iso3' => 'GBR', 'name' => 'United Kingdom'],
        ['iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States'],
        ['iso2' => 'FR', 'iso3' => 'FRA', 'name' => 'France'],
        ['iso2' => 'DE', 'iso3' => 'DEU', 'name' => 'Germany'],
    ];

    /**
     * @var list<array{iso2: string, name: string, line1: string, line2: ?string, city: string, state: ?string, postal_code: string}>
     */
    private const array Addresses = [
        ['iso2' => 'GB', 'name' => 'London studio', 'line1' => '14 Clerkenwell Road', 'line2' => 'Second floor', 'city' => 'London', 'state' => null, 'postal_code' => 'EC1M 5PQ'],
        ['iso2' => 'US', 'name' => 'New York office', 'line1' => '350 Fifth Avenue', 'line2' => 'Suite 4200', 'city' => 'New York', 'state' => 'NY', 'postal_code' => '10118'],
        ['iso2' => 'FR', 'name' => 'Paris showroom', 'line1' => '8 Rue de Rivoli', 'line2' => null, 'city' => 'Paris', 'state' => null, 'postal_code' => '75004'],
    ];

    /**
     * @return array{countries: int, addresses: int}
     */
    public static function run(): array
    {
        self::assertDisposableScreenshotEnvironment();

        $language = Language::query()->where('code', 'en')->first() ?? Language::query()->orderBy('id')->first();

        throw_unless($language instanceof Language, RuntimeException::class, 'Address screenshot fixtures require an installed language.');

        $countryIds = [];

        foreach (self::Countries as $country) {
            $countryIds[$country['iso2']] = self::integerKey(Country::query()->firstOrCreate(
                ['iso2' => $country['iso2']],
                ['iso3' => $country['iso3'], 'name' => $country['name'], 'language_id' => $language->getKey(), 'status' => true],
            )->getKey());
        }

        foreach (self::Addresses as $index => $row) {
            $countryId = $countryIds[$row['iso2']];

            if (Address::findAddress($row['line1'], $row['postal_code'], $countryId) instanceof Address) {
                continue;
            }

            Address::query()->create([
                'country_id' => $countryId,
                'name' => $row['name'],
                'line1' => $row['line1'],
                'line2' => $row['line2'],
                'city' => $row['city'],
                'state' => $row['state'],
                'postal_code' => $row['postal_code'],
                'default' => $index === 0,
                'status' => true,
            ]);
        }

        $address = Address::findAddress('14 Clerkenwell Road', 'EC1M 5PQ', $countryIds['GB']);
        throw_unless($address instanceof Address, RuntimeException::class, 'The screenshot address was not created.');
        foreach (Site::query()->get() as $site) {
            $site->meta = [...($site->meta ?? []), 'address_id' => $address->id];
            $site->save();
        }

        return [
            'countries' => Country::query()->whereIn('iso2', array_column(self::Countries, 'iso2'))->count(),
            'addresses' => Address::query()->whereIn('country_id', $countryIds)->count(),
        ];
    }

    private static function assertDisposableScreenshotEnvironment(): void
    {
        $configuredAppPath = getenv('CAPELL_SCREENSHOT_APP_PATH');
        $basePath = realpath(base_path());
        $appPath = is_string($configuredAppPath) ? realpath($configuredAppPath) : false;
        $environment = app()->bound('config') ? config('app.env') : getenv('APP_ENV');

        throw_unless(
            in_array($environment, ['local', 'testing'], true)
                && in_array(getenv('CAPELL_SCREENSHOT_FIXTURE'), ['1', 'true', 'record-state'], true)
                && is_string($basePath)
                && is_string($appPath)
                && $basePath === $appPath,
            RuntimeException::class,
            'Address screenshot fixtures require the explicit disposable local screenshot environment.',
        );
    }

    private static function integerKey(mixed $key): int
    {
        if (is_int($key)) {
            return $key;
        }

        if (is_string($key) && ctype_digit($key)) {
            return (int) $key;
        }

        throw new RuntimeException('Address screenshot fixtures require integer model keys.');
    }
}
