# Address reference

<!-- prettier-ignore-start -->

Developer reference for `capell-app/address`. Start with the [README](../README.md) for what the extension does and how to install it.

## Package Details

Address is an **Available** Capell package in the **Capell Essentials** product group. It ships as `capell-app/address` and extends these surfaces: admin.

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/address`
- Namespace: `Capell\Address`
- Theme key: not applicable

## Technical Shape

### Service providers

- `Capell\Address\Providers\AddressServiceProvider`
- `Capell\Address\Providers\MigrationMetadataServiceProvider`
- `Capell\Address\Providers\ConsoleServiceProvider`

### Config files

- `packages/address/config/capell-address.php`

### Migrations

- `packages/address/database/migrations/2026_05_10_190839_01_create_countries_table.php`
- `packages/address/database/migrations/2026_05_10_190839_02_create_addresses_table.php`
- `packages/address/database/migrations/2026_07_12_000001_add_address_ownership_and_encrypt_meta.php`

### Models

- `Address`
- `Country`

### Filament classes

- `AddressSelect`
- `CountrySelect`
- `FlagSelect`
- `DefaultAddressConfigurator`
- `DefaultCountryConfigurator`
- `DefaultLanguageConfigurator`
- `AddressResource`
- `ManageAddresses`
- `AddressForm`
- `AddressesTable`
- `CountryResource`
- `ManageCountries`
- `CountryForm`
- `CountriesTable`
- `SiteSchemaExtender`

### Policies

- `AbstractAddressResourcePolicy`
- `AddressPolicy`
- `CountryPolicy`

### Extension contracts

- `AddressGeocodingProvider`
- `AddressValidationProvider`

### Actions

- `BuildAddressPrivacyExportAction`
- `BuildAddressQualityHealthReportAction`
- `BuildGeocodingHttpClientAction`
- `CloneSharedAddressForMutationAction`
- `EnsureSiteOwnsAddressAction`
- `EraseAddressPrivacyDataAction`
- `FindDuplicateAddressGroupsAction`
- `GetAddressNameAction`
- `GetAddressSelectRecordAction`
- `GetCountryNameAction`
- `ImportCountriesAction`
- `InstallAddressPackageAction`
- `ListAddressOptionsAction`
- `ListCountryOptionsAction`
- `NormalizeAddressGeocodingAction`
- `PruneAddressPrivacyDataAction`
- `RehashAddressBlindIndexesAction`
- `ReviewCountryUploadAction`
- `SeedAddressScreenshotFixtureAction`

### Data objects

- `AddressGeocodingResultData`
- `AddressMetaData`
- `AddressQualityHealthReportData`
- `AddressRetentionResultData`
- `AddressValidationResultData`
- `CountryImportReviewData`
- `DuplicateAddressGroupData`
- `ImportCountriesResultData`
- `NormalizeAddressGeocodingResultData`

### Command signatures

- `capell:address-blind-index-rehash`
- `capell:address-countries-import`
- `capell:address-demo`
- `capell:address-faker`
- `capell:address-geocode-normalize`
- `capell:address-install`

### Manifest action API

- `install: Capell\Address\Actions\InstallAddressPackageAction`

### Console command classes

- `DemoCommand`
- `FakerCommand`
- `ImportCountriesCommand`
- `InstallCommand`
- `NormalizeAddressGeocodingCommand`
- `RehashAddressBlindIndexesCommand`
- `SeedAddressScreenshotFixtureCommand`

### Manifest contributions

- `admin-resource: Capell\Address\Manifest\AddressResourceContribution`
- `admin-resource: Capell\Address\Manifest\CountryResourceContribution`
- `asset: Capell\Address\Manifest\AddressAdminAssetsContribution`
- `configurator: Capell\Address\Manifest\AddressConfiguratorsContribution`
- `console-command: Capell\Address\Manifest\AddressConsoleCommandsContribution`
- `health-check: Capell\Address\Health\AddressHealthCheck`
- `migration: Capell\Address\Manifest\AddressMigrationsContribution`
- `model: Capell\Address\Manifest\AddressModelsContribution`
- `schema-extender: Capell\Address\Manifest\AddressSiteSchemaExtenderContribution`

### Health checks

- `Capell\Address\Health\AddressHealthCheck`

### Blade views

- `packages/address/resources/views/components/flag-icon.blade.php`


## Data Model

- Required tables: `countries`, `addresses`.
- Models: `Address`, `Country`.
- Core record references in migrations: `sites via site_id`, `languages via language_id`.
- Migration files: `2026_05_10_190839_01_create_countries_table.php`, `2026_05_10_190839_02_create_addresses_table.php`, `2026_07_12_000001_add_address_ownership_and_encrypt_meta.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: migrations declare null-on-delete relationships; no timed pruning or retention schedule is declared in `capell.json`.

<!-- prettier-ignore-end -->
