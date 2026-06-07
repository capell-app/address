# Address

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin** · Product group: **Capell Foundation**

This page is the consolidated implementation overview for the Address package. It is extracted from the package README, service providers, migrations, config files, routes, resources, models, actions, and the shared Capell ERD notes where available.

## What This Package Adds

Address gives Capell a shared country list and reusable postal address records for admin surfaces and package forms.

- Filament resources for countries and addresses.
- Address, country, and flag form components for other packages.
- Site schema extension support where address details are needed.
- Install, demo, and faker commands for local package data.

## Developer Notes

Provides Country and Address models, typed address metadata, Filament configurators, observers, and support classes for flag rendering.

- AddressServiceProvider registers the package.
- Migrations create countries and addresses.
- Models: Country and Address.
- Filament resources: CountryResource and AddressResource.
- Form components: AddressSelect, CountrySelect, FlagSelect.
- Observers keep model state consistent.

## Operational Notes

Keeps location data consistent across structured websites instead of duplicating country and address fields in separate features.

- Adds country and address admin navigation.
- Adds database tables for countries and addresses.
- Adds address/country form components for package developers.
- No public route is registered by this package.

## Data And Retention

- countries stores localized country names with iso2 and iso3 codes.
- addresses stores line, city, state, postal code, and country relationship data.
- Countries connect to core languages.
- Deletion behaviour should be verified before documenting cascading rules.

## Screenshot Plan

- Countries admin index.
- Addresses admin index.
- Create/edit country form.
- Create/edit address form.
- Site settings fields where address data is injected.
- Dark mode variants for the country index, address index, country form, address form, and site settings field.

## Screenshots

![Countries admin index](screenshots/countries-admin-index.png)

![Addresses admin index](screenshots/addresses-admin-index.png)

![Address fields injected into the site form](screenshots/site-settings-fields-where-address-data-is-injected.png)

Country and address records are managed through Filament table actions in the current app. The full screenshot manifest also captures create/edit modals and dark mode variants for marketplace galleries.

## Pitfalls

- Run migrations before opening the resources.
- Seed or import countries before expecting useful address selectors.
- Check language records before relying on localized country names.

## Verification

- Run `vendor/bin/pest packages/address/tests` when package tests exist.
- Run the relevant host-app migration or package install flow in a disposable database.
- Open the listed admin or frontend surface and compare it with the screenshot plan.

## Package Manifest

- Composer name: `capell-app/address`
- Product group: Capell Foundation
- Kind: package
- Tier: free
- Bundle: foundation
- Contexts: `admin`
- Requires: `capell-app/admin`
- Optional dependencies: None listed.

## Admin Surfaces

- AddressResource (packages/address/src/Filament/Resources/Addresses/AddressResource.php)
- ManageAddresses (packages/address/src/Filament/Resources/Addresses/Pages/ManageAddresses.php)
- CountryResource (packages/address/src/Filament/Resources/Countries/CountryResource.php)
- ManageCountries (packages/address/src/Filament/Resources/Countries/Pages/ManageCountries.php)

## Commands

- `capell:address-demo {--sites=}` (packages/address/src/Console/Commands/DemoCommand.php)
- `capell:address-faker {--count=25} {--force}` (packages/address/src/Console/Commands/FakerCommand.php)
- `capell:address-install` (packages/address/src/Console/Commands/InstallCommand.php)

## Routes And Config

- None proven in this package directory.

## Permissions And Gates

- None proven in this package directory.

## Migrations

- Migration: 2026_05_10_190839_01_create_countries_table.php
- Migration: 2026_05_10_190839_02_create_addresses_table.php

## ERD Excerpt

```mermaid
erDiagram
    COUNTRIES ||--o{ ADDRESSES : contains
    LANGUAGES ||--o{ COUNTRIES : localizes

    COUNTRIES {
        bigint id PK
        bigint language_id FK
        string name
        string iso2
        string iso3
    }

    ADDRESSES {
        bigint id PK
        bigint country_id FK
        string line1
        string line2
        string city
        string state
        string postal_code
    }
```

## Screenshot Automation

Deployment should read [screenshots.json](screenshots.json), install the package with demo data, resolve each admin surface or frontend URL, and write images to `packages/address/docs/screenshots`.

- Countries admin index.
- Addresses admin index.
- Create/edit country form.
- Create/edit address form.
- Site settings fields where address data is injected.
- Dark mode variants for all captured Address admin workflows.
