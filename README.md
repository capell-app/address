# Address

<!-- prettier-ignore-start -->

## What This Plugin Adds

Address is an **Available**, **Schema-owning** Capell package in the **Capell Foundation** product group. It ships as `capell-app/address` and extends these surfaces: admin.

Address adds site-scoped country and postal-address records, admin resources, and shared address selectors for other Capell packages.

Admins manage countries and reusable addresses, then select saved address data while editing a site.

Evidence: [`capell.json`](capell.json), [`src/Models/Address.php`](src/Models/Address.php), [`src/Models/Country.php`](src/Models/Country.php), [`docs/overview.admin.md`](docs/overview.admin.md), [`docs/screenshots.json`](docs/screenshots.json), [`tests/Feature/Filament/Resources/Sites/Schemas/SiteSchemaExtenderTest.php`](tests/Feature/Filament/Resources/Sites/Schemas/SiteSchemaExtenderTest.php).

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/address`
- Namespace: `Capell\Address`
- Theme key: not applicable

## Why It Matters

**For developers:** The package contributes AddressResource, CountryResource, reusable form fields, and a site schema extender instead of making each package model location fields again.

**For teams:** A postal address can be corrected once and reused wherever a Capell workflow references it.

Evidence: [`capell.json`](capell.json), [`src/Providers/AddressServiceProvider.php`](src/Providers/AddressServiceProvider.php), [`src/Filament/Resources/Sites/Schemas/Extenders/SiteSchemaExtender.php`](src/Filament/Resources/Sites/Schemas/Extenders/SiteSchemaExtender.php), [`tests/Unit/AddressProviderContractsTest.php`](tests/Unit/AddressProviderContractsTest.php), [`docs/overview.admin.md`](docs/overview.admin.md), [`docs/screenshots.json`](docs/screenshots.json), [`tests/Feature/Filament/Resources/Address/AddressResourceTest.php`](tests/Feature/Filament/Resources/Address/AddressResourceTest.php).

## Screens And Workflow

Screenshot contract: `docs/screenshots.json`.

![Countries admin index](docs/screenshots/countries-admin-index.png)

![Addresses admin index](docs/screenshots/addresses-admin-index.png)

- Countries admin index (admin, required).
- Addresses admin index (admin, required).
- Create/edit country form (admin, optional).
- Create/edit address form (admin, optional).
- Site settings fields where address data is injected (admin, optional).

## Technical Shape

- Service providers: `Capell\Address\Providers\AddressServiceProvider`.
- Migrations: `packages/address/database/migrations/2026_05_10_190839_01_create_countries_table.php`, `packages/address/database/migrations/2026_05_10_190839_02_create_addresses_table.php`, `packages/address/database/migrations/2026_07_12_000001_add_address_ownership_and_encrypt_meta.php`.
- Models: `Address`, `Country`.
- Filament classes: `AddressSelect`, `CountrySelect`, `FlagSelect`, `DefaultAddressConfigurator`, `DefaultCountryConfigurator`, `DefaultLanguageConfigurator`, `AddressResource`, `ManageAddresses`, `AddressForm`, `AddressesTable`, `CountryResource`, `ManageCountries`, `and 3 more`.
- Policies: `AbstractAddressResourcePolicy`, `AddressPolicy`, `CountryPolicy`.
- Extension contracts: `AddressGeocodingProvider`, `AddressValidationProvider`.
- Actions: `BuildAddressPrivacyExportAction`, `BuildAddressQualityHealthReportAction`, `CloneSharedAddressForMutationAction`, `EnsureSiteOwnsAddressAction`, `EraseAddressPrivacyDataAction`, `FindDuplicateAddressGroupsAction`, `GetAddressNameAction`, `GetAddressSelectRecordAction`, `GetCountryNameAction`, `ImportCountriesAction`, `InstallAddressPackageAction`, `ListAddressOptionsAction`, `and 2 more`.
- Data objects: `AddressGeocodingResultData`, `AddressMetaData`, `AddressQualityHealthReportData`, `AddressValidationResultData`, `DuplicateAddressGroupData`, `ImportCountriesResultData`, `NormalizeAddressGeocodingResultData`.
- Command signatures: `capell:address-countries-import`, `capell:address-demo`, `capell:address-faker`, `capell:address-geocode-normalize`, `capell:address-install`.
- Manifest action API: `install: Capell\Address\Actions\InstallAddressPackageAction`.
- Console command classes: `DemoCommand`, `FakerCommand`, `ImportCountriesCommand`, `InstallCommand`, `NormalizeAddressGeocodingCommand`.
- Manifest contributions: `admin-resource: Capell\Address\Manifest\AddressResourceContribution`, `admin-resource: Capell\Address\Manifest\CountryResourceContribution`, `asset: Capell\Address\Manifest\AddressAdminAssetsContribution`, `configurator: Capell\Address\Manifest\AddressConfiguratorsContribution`, `console-command: Capell\Address\Manifest\AddressConsoleCommandsContribution`, `health-check: Capell\Address\Health\AddressHealthCheck`, `migration: Capell\Address\Manifest\AddressMigrationsContribution`, `model: Capell\Address\Manifest\AddressModelsContribution`, `schema-extender: Capell\Address\Manifest\AddressSiteSchemaExtenderContribution`.
- Health checks: `Capell\Address\Health\AddressHealthCheck`.
- Blade views: `packages/address/resources/views/components/flag-icon.blade.php`.

## Data Model

- Required tables: `countries`, `addresses`.
- Models: `Address`, `Country`.
- Core record references in migrations: `sites via site_id`, `languages via language_id`.
- Migration files: `2026_05_10_190839_01_create_countries_table.php`, `2026_05_10_190839_02_create_addresses_table.php`, `2026_07_12_000001_add_address_ownership_and_encrypt_meta.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: migrations declare null-on-delete relationships; no timed pruning or retention schedule is declared in `capell.json`.

## Install Impact

- Required packages: `capell-app/admin`.
- Admin navigation: declares `admin-resource: AddressResourceContribution`, `admin-resource: CountryResourceContribution`; each Filament page or resource controls its own navigation visibility.
- Admin/editor extensions: `configurator: AddressConfiguratorsContribution`, `schema-extender: AddressSiteSchemaExtenderContribution`.
- Permissions: none declared in `capell.json`.
- Public routes: none declared.
- Database changes: package migrations are declared.
- Config: no package config files.
- Settings: no package settings declared.
- Queues or schedules: none declared.
- Cache tags: none declared.
- Commands: `capell:address-countries-import`, `capell:address-demo`, `capell:address-faker`, `capell:address-geocode-normalize`, `capell:address-install`.

## Common Pitfalls

- Keep required Capell packages on compatible v4 releases: `capell-app/admin`.
- Run migrations before opening package resources or public routes.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |

## Quick Start

1. Install the package: `composer require capell-app/address`.
2. Run the required setup: `php artisan capell:address-install`.
3. Open the Countries admin index and confirm the admin workflow loads.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Admin guide](docs/admin-guide.md)
- [Troubleshooting](#troubleshooting)
- [Screenshot contract](docs/screenshots.json)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Related packages: [Bookings](../bookings/README.md), [Events](../events/README.md).
- Focused tests: `vendor/bin/pest packages/address/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
