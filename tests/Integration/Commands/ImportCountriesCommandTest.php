<?php

declare(strict_types=1);

use Capell\Address\Models\Country;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('imports JSON country datasets and can restore or disable countries', function (): void {
    Country::factory()->create([
        'name' => 'Old France',
        'iso2' => 'FR',
        'iso3' => 'FRA',
        'status' => false,
    ]);
    Country::factory()->create([
        'name' => 'Germany',
        'iso2' => 'DE',
        'iso3' => 'DEU',
        'status' => true,
    ]);
    Country::factory()->create([
        'name' => 'Old Spain',
        'iso2' => 'ES',
        'iso3' => 'ESP',
        'status' => false,
    ])->delete();

    $path = addressCountryDatasetPath('countries.json');
    File::put($path, json_encode([
        'countries' => [
            ['name' => 'France', 'iso2' => 'fr', 'iso3' => 'fra'],
            ['name' => 'Spain', 'iso2' => 'ES', 'iso3' => 'ESP'],
            ['name' => 'Canada', 'iso2' => 'CA', 'iso3' => 'CAN'],
            ['name' => '', 'iso2' => 'ZZ', 'iso3' => 'ZZZ'],
        ],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('capell:address-countries-import', [
        'path' => $path,
        '--disable-missing' => true,
        '--restore' => true,
    ])
        ->expectsOutputToContain('Country import completed')
        ->expectsOutputToContain('Created: 1')
        ->expectsOutputToContain('Updated: 1')
        ->expectsOutputToContain('Restored: 1')
        ->expectsOutputToContain('Disabled: 1')
        ->expectsOutputToContain('Skipped: 1')
        ->assertSuccessful();

    expect(Country::query()->where('iso2', 'FR')->firstOrFail())
        ->name->toBe('France')
        ->status->toBeTrue()
        ->and(Country::query()->where('iso2', 'CA')->exists())->toBeTrue()
        ->and(Country::withTrashed()->where('iso2', 'ES')->firstOrFail()->trashed())->toBeFalse()
        ->and(Country::query()->where('iso2', 'DE')->firstOrFail()->status)->toBeFalse();
});

it('dry runs country imports without writing records', function (): void {
    $path = addressCountryDatasetPath('dry-run-countries.json');
    File::put($path, json_encode([
        ['name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR'],
    ], JSON_THROW_ON_ERROR));

    $this->artisan('capell:address-countries-import', [
        'path' => $path,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Country import dry run found 1 country record')
        ->assertSuccessful();

    expect(Country::query()->where('iso2', 'GB')->exists())->toBeFalse();
});

it('imports CSV country datasets', function (): void {
    $path = addressCountryDatasetPath('countries.csv');
    File::put($path, "name,iso2,iso3\nAustralia,AU,AUS\nNew Zealand,NZ,NZL\n");

    $this->artisan('capell:address-countries-import', [
        'path' => $path,
    ])
        ->expectsOutputToContain('Created: 2')
        ->assertSuccessful();

    expect(Country::query()->where('iso2', 'AU')->exists())->toBeTrue()
        ->and(Country::query()->where('iso3', 'NZL')->exists())->toBeTrue();
});

function addressCountryDatasetPath(string $name): string
{
    $directory = storage_path('framework/testing/address-country-import-' . Str::random(8));
    File::ensureDirectoryExists($directory);

    return $directory . '/' . $name;
}
