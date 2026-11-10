# Address

<!-- prettier-ignore-start -->

## What It Does

Address adds site-scoped country and postal-address records, admin resources, and shared address selectors for other Capell packages.

- **Price and availability:** Check current availability and price on the [Capell Marketplace listing](https://capell.app/extensions/address).
- **Licence:** Check the licence terms on the [Capell Marketplace listing](https://capell.app/extensions/address).
- **Requires:** `PHP ^8.4`, `capell-app/admin ^1.0.66`, `capell-app/core ^1.0.75`, `stijnvanouplines/blade-country-flags ^1.0`.
- **Install:** `composer require capell-app/address`.

Admins manage countries and reusable addresses, then select saved address data while editing a site.

## Who It Is For

**For developers:** The package contributes AddressResource, CountryResource, reusable form fields, and a site schema extender instead of making each package model location fields again.

**For teams:** A postal address can be corrected once and reused wherever a Capell workflow references it.

## Quick Start

1. Install the package: `composer require capell-app/address`.
2. Run the package setup: `php artisan capell:address-install`.
3. See it working: run `php artisan capell:address-demo`.
4. Open the package admin surface at `/admin/address/countries` and confirm Address is available.

## Configure

- **Commands:** `php artisan capell:address-countries-import`, `php artisan capell:address-demo`, `php artisan capell:address-faker`, `php artisan capell:address-geocode-normalize`, `php artisan capell:address-install`.

## Limits And Pitfalls

- Install the required Capell packages at the versions in `composer.json`: `capell-app/admin ^1.0.66`, `capell-app/core ^1.0.75`.
- Run migrations before opening package resources or public routes.

## Screens And Workflow

![Countries admin index](docs/screenshots/countries-admin-index.png)

![Addresses admin index](docs/screenshots/addresses-admin-index.png)

- Countries admin index (admin).
- Addresses admin index (admin).
- Create/edit country form (admin).
- Create/edit address form (admin).
- Site settings fields where address data is injected (admin).
- Countries admin index with admin sidebar menu open (admin).

## Install Impact

- Required packages: `capell-app/admin`, `capell-app/core`.
- Admin navigation: declares `admin-resource: AddressResourceContribution`, `admin-resource: CountryResourceContribution`; each Filament page or resource controls its own navigation visibility.
- Admin/editor extensions: `configurator: AddressConfiguratorsContribution`, `schema-extender: AddressSiteSchemaExtenderContribution`.
- Permissions: access also governed by package policies: `AbstractAddressResourcePolicy`, `AddressPolicy`, `CountryPolicy`.
- Public routes: none declared.
- Database changes: package migrations are declared.
- Config: no package config files.
- Settings: no package settings declared.
- Queues or schedules: none declared.
- Cache tags: none declared.
- Commands: `capell:address-countries-import`, `capell:address-demo`, `capell:address-faker`, `capell:address-geocode-normalize`, `capell:address-install`.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |

## Upgrading

No manual steps between minor versions.

Read [CHANGELOG.md](CHANGELOG.md) before upgrading.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Worked extension examples](docs/extension-contracts.md)
- [Admin guide](docs/admin-guide.md)
- [Troubleshooting](#troubleshooting)
- [Developer reference](docs/reference.md)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Related packages: [Bookings](../bookings/README.md), [Events](../events/README.md), [Privacy Centre](../privacy-center/README.md).

<!-- prettier-ignore-end -->
