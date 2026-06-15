<?php

declare(strict_types=1);

use Capell\Address\Actions\BuildAddressQualityHealthReportAction;
use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Contracts\AddressValidationProvider;
use Capell\Address\Data\AddressGeocodingResultData;
use Capell\Address\Data\AddressQualityHealthReportData;
use Capell\Address\Data\AddressValidationResultData;
use Capell\Address\Health\AddressHealthCheck;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;

it('defines validation and geocoding provider result contracts', function (): void {
    $validationProvider = new class implements AddressValidationProvider
    {
        public function key(): string
        {
            return 'fake-validation';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function validate(Address $address): AddressValidationResultData
        {
            return new AddressValidationResultData(
                provider: $this->key(),
                valid: $address->line1 !== null,
                confidence: 0.9,
                messages: [],
                normalized: ['line1' => $address->line1],
            );
        }
    };

    $geocodingProvider = new class implements AddressGeocodingProvider
    {
        public function key(): string
        {
            return 'fake-geocoding';
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
    };

    $address = new Address(['line1' => '10 Downing Street']);

    expect($validationProvider->validate($address))
        ->toBeInstanceOf(AddressValidationResultData::class)
        ->valid->toBeTrue()
        ->and($geocodingProvider->geocode($address))
        ->toBeInstanceOf(AddressGeocodingResultData::class)
        ->latitude->toBe('51.5074');
});

it('builds address quality health reports for country and coordinate coverage', function (): void {
    $country = Country::factory()->create(['status' => true]);
    Address::factory()->create([
        'country_id' => $country->getKey(),
        'meta' => [
            'latitude' => '51.5074',
            'longitude' => '-0.1278',
        ],
    ]);
    Address::factory()->create([
        'country_id' => null,
        'meta' => [
            'latitude' => '100',
            'longitude' => '200',
        ],
    ]);

    app()->bind('address.validation.fake', fn (): AddressValidationProvider => new class implements AddressValidationProvider
    {
        public function key(): string
        {
            return 'fake-validation';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function validate(Address $address): AddressValidationResultData
        {
            return new AddressValidationResultData($this->key(), true);
        }
    });
    app()->tag(['address.validation.fake'], AddressValidationProvider::TAG);

    app()->bind('address.geocoding.fake', fn (): AddressGeocodingProvider => new class implements AddressGeocodingProvider
    {
        public function key(): string
        {
            return 'fake-geocoding';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function geocode(Address $address): AddressGeocodingResultData
        {
            return new AddressGeocodingResultData($this->key(), '51.5074', '-0.1278');
        }
    });
    app()->tag(['address.geocoding.fake'], AddressGeocodingProvider::TAG);

    $report = BuildAddressQualityHealthReportAction::run();

    expect($report)
        ->toBeInstanceOf(AddressQualityHealthReportData::class)
        ->status->toBe('failed')
        ->checkedAddresses->toBe(2)
        ->missingCountries->toBe(1)
        ->invalidCoordinates->toBe(1)
        ->validationProviders->toBe(['fake-validation'])
        ->geocodingProviders->toBe(['fake-geocoding'])
        ->and($report->issues)->toContain('1 address(es) missing an enabled country.')
        ->and($report->issues)->toContain('1 address(es) with invalid latitude or longitude metadata.')
        ->and(AddressHealthCheck::report())->toBeInstanceOf(AddressQualityHealthReportData::class)
        ->and(AddressHealthCheck::passed())->toBeFalse();

    $diagnostics = AddressHealthCheck::runDiagnostics();

    expect($diagnostics)->toHaveCount(3)
        ->and($diagnostics->every(fn (DoctorCheckResultData $result): bool => $result->label !== ''))->toBeTrue()
        ->and($diagnostics->firstWhere('label', 'Address data quality')?->passed)->toBeFalse();

    $collectionReport = BuildAddressQualityHealthReportAction::run(
        Address::query()->with('country')->get(),
    );

    expect($collectionReport)
        ->toBeInstanceOf(AddressQualityHealthReportData::class)
        ->status->toBe('failed')
        ->checkedAddresses->toBe(2)
        ->missingCountries->toBe(1)
        ->invalidCoordinates->toBe(1)
        ->and($collectionReport->issues)->toContain('Address #2 is missing an enabled country.');
});

it('does not report missing optional providers as health issues', function (): void {
    $country = Country::factory()->create(['status' => true]);

    Address::factory()->create([
        'country_id' => $country->getKey(),
        'meta' => [
            'latitude' => '51.5074',
            'longitude' => '-0.1278',
        ],
    ]);

    expect(BuildAddressQualityHealthReportAction::run())
        ->status->toBe('passed')
        ->validationProviders->toBe([])
        ->geocodingProviders->toBe([])
        ->issues->toBe([]);

    expect(AddressHealthCheck::passed())->toBeTrue()
        ->and(AddressHealthCheck::runDiagnostics()->every(fn (DoctorCheckResultData $result): bool => $result->passed))->toBeTrue();
});
