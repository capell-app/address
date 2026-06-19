# Address

<!-- prettier-ignore-start -->

## What This Plugin Adds

Address is an **Available**, **Schema-owning** Capell package in the **Capell Foundation** product group. It ships as `capell-app/address` and extends these surfaces: admin.

Reusable countries and structured postal addresses for Capell: one shared address record, site-scoped, that any package can reference instead of re-modelling location fields.

After install, admins get package-owned management or reporting surfaces inside Capell.

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/address`
- Namespace: `Capell\Address`
- Theme key: not applicable

## Why It Matters

**For developers:** The package gives developers package-owned service providers, Actions, Data objects, models, Filament classes, and Blade views instead of pushing this behaviour into core or application code.

**For teams:** Reusable countries and structured postal addresses for Capell: one shared address record, site-scoped, that any package can reference instead of re-modelling location fields.

## Screens And Workflow

Screenshot contract: `screenshots.json`.

- Countries admin index (admin, required).
- Addresses admin index (admin, required).
- Create/edit country form (admin, required).
- Create/edit address form (admin, required).
- Site settings fields where address data is injected (admin, required).

## Screenshot Evidence

These captures are the package-owned visual contract for the admin pages, public pages, actions, workflows, and feature surfaces described above. Keep this section aligned with `docs/screenshots.json` whenever the package surface changes.

### Countries admin index

![Countries admin index](screenshots/countries-admin-index.png)

- Surface: admin · Target: CountryResource.
- Documents: An administrator reviews available countries before using country and address selectors elsewhere in Capell.
- Capture notes: Requires package migrations and seeded country rows with ISO2/ISO3 codes and language relationships.

### Addresses admin index

![Addresses admin index](screenshots/addresses-admin-index.png)

- Surface: admin · Target: AddressResource.
- Documents: An administrator manages reusable postal addresses that other packages can reference instead of duplicating location fields.
- Capture notes: Requires seeded addresses connected to seeded countries. Include at least one address with full line, city, state, postal code, and country data.

### Create/edit country form

![Create/edit country form](screenshots/create-edit-country-form.png)

- Surface: admin · Target: CountryResource.
- Documents: An administrator adds or corrects a country name, ISO code, and language mapping.
- Capture notes: Open the create or edit modal from `CountryResource`; the current resource uses table actions rather than a separate create page.

### Create/edit address form

![Create/edit address form](screenshots/create-edit-address-form.png)

- Surface: admin · Target: AddressResource.
- Documents: An administrator creates a structured address with country, street, locality, region, and postal fields.
- Capture notes: Open the create or edit modal from `AddressResource`; seed countries first so the country selector is meaningful.

### Site settings fields where address data is injected

![Site settings fields where address data is injected](screenshots/site-settings-fields-where-address-data-is-injected.png)

- Surface: admin · Target: SiteSchemaExtender.
- Documents: A site administrator selects reusable address data while editing site-level settings.
- Capture notes: Requires the host admin site form that consumes `SiteSchemaExtender` plus seeded countries and addresses.

## Technical Shape

- Service providers: `Capell\Address\Providers\AddressServiceProvider`.
- Migrations: `packages/address/database/migrations/2026_05_10_190839_01_create_countries_table.php`, `packages/address/database/migrations/2026_05_10_190839_02_create_addresses_table.php`.
- Models: `Address`, `Country`.
- Filament classes: `AddressSelect`, `CountrySelect`, `FlagSelect`, `DefaultAddressConfigurator`, `DefaultCountryConfigurator`, `DefaultLanguageConfigurator`, `AddressResource`, `ManageAddresses`, `AddressForm`, `AddressesTable`, `CountryResource`, `ManageCountries`, `and 3 more`.
- Policies: `AbstractAddressResourcePolicy`, `AddressPolicy`, `CountryPolicy`.
- Actions: `BuildAddressQualityHealthReportAction`, `FindDuplicateAddressGroupsAction`, `ImportCountriesAction`, `InstallAddressPackageAction`, `NormalizeAddressGeocodingAction`.
- Data objects: `AddressGeocodingResultData`, `AddressMetaData`, `AddressQualityHealthReportData`, `AddressValidationResultData`, `DuplicateAddressGroupData`, `ImportCountriesResultData`, `NormalizeAddressGeocodingResultData`.
- Provider contracts: `AddressValidationProvider::TAG` and `AddressGeocodingProvider::TAG` let companion packages register optional validation and geocoding providers; available provider keys appear in address quality reports.
- Command signatures: `capell:address-countries-import`, `capell:address-demo`, `capell:address-faker`, `capell:address-geocode-normalize`, `capell:address-install`.
- Console command classes: `DemoCommand`, `FakerCommand`, `ImportCountriesCommand`, `InstallCommand`, `NormalizeAddressGeocodingCommand`.
- Manifest contributions: `admin-resource: Capell\Address\Manifest\AddressResourceContribution`, `admin-resource: Capell\Address\Manifest\CountryResourceContribution`, `asset: Capell\Address\Manifest\AddressAdminAssetsContribution`, `configurator: Capell\Address\Manifest\AddressConfiguratorsContribution`, `console-command: Capell\Address\Manifest\AddressConsoleCommandsContribution`, `health-check: Capell\Address\Health\AddressHealthCheck`, `migration: Capell\Address\Manifest\AddressMigrationsContribution`, `model: Capell\Address\Manifest\AddressModelsContribution`, `schema-extender: Capell\Address\Manifest\AddressSiteSchemaExtenderContribution`.
- Health checks: `Capell\Address\Health\AddressHealthCheck`.
- Blade views: `packages/address/resources/views/components/flag-icon.blade.php`.

## Data Model

- Required tables: `countries`, `addresses`.
- Models: `Address`, `Country`.
- Migration files: `2026_05_10_190839_01_create_countries_table.php`, `2026_05_10_190839_02_create_addresses_table.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: Docs gap unless the package has an explicit pruning command, retention setting, or tested cascade path.

## Install Impact

- Admin navigation: adds package-owned Filament classes when registered.
- Permissions: none declared in `capell.json`.
- Public routes: none detected in package route files.
- Database changes: package migrations are declared.
- Settings: no package settings declared.
- Queues or schedules: none detected in standard package paths.
- Cache tags: none declared.
- Commands: `capell:address-countries-import`, `capell:address-demo`, `capell:address-faker`, `capell:address-geocode-normalize`, `capell:address-install`.

Country datasets can be refreshed with `capell:address-countries-import /path/to/countries.json` or a CSV file with `name`, `iso2`, and `iso3` columns. Use `--dry-run` before changing production data, `--restore` to restore soft-deleted ISO matches, and `--disable-missing` only when the dataset is authoritative for the installation.

Address records can be personal data. Consuming packages should include the address fields they use in their own subject exports, treat coordinates as precise location data, and detach rather than delete shared address records during erasure. See [Address API](address-api.md) for the full export/erase guidance.

Optional geocoding providers can normalize coordinates with `capell:address-geocode-normalize`. Use `--dry-run`, `--limit`, and `--provider=provider-key` to review or narrow a batch before writing latitude, longitude, provider key, confidence, and timestamp metadata.

## Common Pitfalls

- Run migrations before opening package resources or public routes.
- Treat duplicate address diagnostics as a non-destructive report: review the grouped record IDs before introducing package-specific merge or cleanup behavior.
- Keep `composer.json`, `composer.local.json`, `capell.json`, docs, screenshots, and tests aligned when the package surface changes.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |
| Address health reports duplicate groups | Multiple records normalize to the same country, postal code, and address lines | Call `FindDuplicateAddressGroupsAction` or inspect `AddressHealthCheck::report()` | Review the grouped record IDs and decide whether the consuming workflow needs a merge, disable, or manual cleanup path |
| Background work does not run | Queue worker or scheduled command is not active | Check package jobs, commands, and host scheduler configuration | Start the queue or scheduler, then run the focused command or package test |

## Quick Start

1. Install the package: `composer require capell-app/address`.
2. Run the required setup: `php artisan capell:address-install`.
3. Open the related Capell admin surface and verify Address appears.

## Next Steps

- [Package docs index](README.md)
- [Screenshot contract](screenshots.json)
- [Marketplace assets](assets/marketplace/)
- [Capell content language plan](../../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../../docs/erd/capell-and-package-erds.md)
- Related packages: [Bookings](../../bookings/README.md), [Events](../../events/README.md), [Theme Local Services](../../theme-local-services/README.md).
- Focused tests: `vendor/bin/pest packages/address/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
