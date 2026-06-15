<?php

declare(strict_types=1);

namespace Capell\Address\Health;

use Capell\Address\Actions\BuildAddressQualityHealthReportAction;
use Capell\Address\Contracts\AddressGeocodingProvider;
use Capell\Address\Contracts\AddressValidationProvider;
use Capell\Address\Data\AddressQualityHealthReportData;
use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class AddressHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }

    public static function report(): AddressQualityHealthReportData
    {
        return BuildAddressQualityHealthReportAction::run();
    }

    /**
     * @return Collection<int, DoctorCheckResultData>
     */
    public static function runDiagnostics(): Collection
    {
        $check = new self;

        return collect([
            $check->storageTablesCheck(),
            $check->dataQualityCheck(),
            $check->providerExtensionPointCheck(),
        ]);
    }

    public static function passed(): bool
    {
        return self::runDiagnostics()
            ->every(static fn (DoctorCheckResultData $result): bool => $result->passed);
    }

    public function storageTablesCheck(): DoctorCheckResultData
    {
        $missingTables = $this->missingTables();

        return new DoctorCheckResultData(
            label: 'Address storage tables',
            passed: $missingTables === [],
            message: $missingTables === []
                ? 'The countries and addresses tables are present.'
                : 'Missing tables: ' . implode(', ', $missingTables) . '.',
            remediation: $missingTables === []
                ? null
                : 'Run the Address package migrations before using address records.',
        );
    }

    public function dataQualityCheck(): DoctorCheckResultData
    {
        $missingTables = $this->missingTables();

        if ($missingTables !== []) {
            return new DoctorCheckResultData(
                label: 'Address data quality',
                passed: false,
                message: 'Address data quality could not be checked because storage tables are missing.',
                remediation: 'Run the Address package migrations, then rerun diagnostics.',
            );
        }

        $report = self::report();
        $passed = $report->status !== 'failed';

        return new DoctorCheckResultData(
            label: 'Address data quality',
            passed: $passed,
            message: $passed
                ? sprintf('Checked %d address(es); no blocking country or coordinate issues were found.', $report->checkedAddresses)
                : sprintf(
                    'Checked %d address(es); %d missing enabled country and %d invalid coordinate issue(s) were found.',
                    $report->checkedAddresses,
                    $report->missingCountries,
                    $report->invalidCoordinates,
                ),
            remediation: $passed
                ? null
                : 'Review Address records with missing countries or invalid latitude/longitude metadata.',
        );
    }

    public function providerExtensionPointCheck(): DoctorCheckResultData
    {
        return new DoctorCheckResultData(
            label: 'Address validation and geocoding providers',
            passed: true,
            message: sprintf(
                'Detected %d validation provider(s) and %d geocoding provider(s). Providers are optional extension points.',
                $this->availableProviderCount(AddressValidationProvider::TAG),
                $this->availableProviderCount(AddressGeocodingProvider::TAG),
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function missingTables(): array
    {
        return array_values(array_filter(
            ['countries', 'addresses'],
            static fn (string $tableName): bool => ! Schema::hasTable($tableName),
        ));
    }

    private function availableProviderCount(string $tag): int
    {
        $count = 0;

        foreach (app()->tagged($tag) as $provider) {
            if (! is_object($provider) || ! method_exists($provider, 'isAvailable') || ! $provider->isAvailable()) {
                continue;
            }

            $count++;
        }

        return $count;
    }
}
