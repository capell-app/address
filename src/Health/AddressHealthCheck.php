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
        return '^1.0';
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
            $check->duplicateAddressQualityCheck(),
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
            label: (string) __('capell-address::health.storage_tables_label'),
            passed: $missingTables === [],
            message: $missingTables === []
                ? (string) __('capell-address::health.storage_tables_passed')
                : (string) __('capell-address::health.storage_tables_failed', ['tables' => implode(', ', $missingTables)]),
            remediation: $missingTables === []
                ? null
                : (string) __('capell-address::health.storage_tables_remediation'),
            id: 'legacy.address.storage.tables',
        );
    }

    public function dataQualityCheck(): DoctorCheckResultData
    {
        $missingTables = $this->missingTables();

        if ($missingTables !== []) {
            return new DoctorCheckResultData(
                label: (string) __('capell-address::health.data_quality_label'),
                passed: false,
                message: (string) __('capell-address::health.data_quality_missing_tables'),
                remediation: (string) __('capell-address::health.data_quality_missing_tables_remediation'),
                id: 'legacy.address.data.quality',
            );
        }

        $report = self::report();
        $passed = $report->status !== 'failed';

        return new DoctorCheckResultData(
            label: (string) __('capell-address::health.data_quality_label'),
            passed: $passed,
            message: $passed
                ? (string) __('capell-address::health.data_quality_passed', ['addresses' => $report->checkedAddresses])
                : (string) __('capell-address::health.data_quality_failed', [
                    'addresses' => $report->checkedAddresses,
                    'countries' => $report->missingCountries,
                    'coordinates' => $report->invalidCoordinates,
                ]),
            remediation: $passed
                ? null
                : (string) __('capell-address::health.data_quality_remediation'),
            id: 'legacy.address.data.quality',
        );
    }

    public function duplicateAddressQualityCheck(): DoctorCheckResultData
    {
        $missingTables = $this->missingTables();

        if ($missingTables !== []) {
            return new DoctorCheckResultData(
                label: (string) __('capell-address::health.duplicate_addresses_label'),
                passed: false,
                message: (string) __('capell-address::health.duplicate_addresses_missing_tables'),
                remediation: (string) __('capell-address::health.data_quality_missing_tables_remediation'),
                id: 'legacy.duplicate.address.quality',
            );
        }

        $report = self::report();
        $passed = $report->duplicateGroups === [];

        return new DoctorCheckResultData(
            label: (string) __('capell-address::health.duplicate_addresses_label'),
            passed: $passed,
            message: $passed
                ? (string) __('capell-address::health.duplicate_addresses_passed', ['addresses' => $report->checkedAddresses])
                : (string) __('capell-address::health.duplicate_addresses_failed', [
                    'addresses' => $report->checkedAddresses,
                    'duplicates' => $report->duplicateAddresses,
                    'groups' => count($report->duplicateGroups),
                ]),
            remediation: $passed
                ? null
                : (string) __('capell-address::health.duplicate_addresses_remediation'),
            id: 'legacy.duplicate.address.quality',
        );
    }

    public function providerExtensionPointCheck(): DoctorCheckResultData
    {
        return new DoctorCheckResultData(
            label: (string) __('capell-address::health.providers_label'),
            passed: true,
            message: (string) __('capell-address::health.providers_message', [
                'validation' => $this->availableProviderCount(AddressValidationProvider::TAG),
                'geocoding' => $this->availableProviderCount(AddressGeocodingProvider::TAG),
            ]),
            id: 'legacy.address.validation.and.geocoding.providers',
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
