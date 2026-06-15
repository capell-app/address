# Address - Improvement & Growth Plan

> Package: capell-app/address · Kind: package · Tier: free · Product group: Capell Foundation · Bundle: foundation · Status: Active

## 1. Snapshot

Address provides reusable, site-scoped countries and postal addresses for Capell. It owns address/country migrations, admin resources, model relations on `Site`, Filament field components/configurators, flag rendering, provider contracts for validation/geocoding, demo/faker/install/country-import commands, screenshot coverage, and quality report Actions. The package is a strong foundation layer, and the current improvement wave has closed manifest traceability, health diagnostics, duplicate detection, provider docs, and country dataset refresh/import coverage.

## 2. Improvements (existing functionality)

1. **Promote address quality into real health diagnostics.** `AddressHealthCheck` exposes `report()` but no `passed()` or diagnostic collection. Add `runDiagnostics()` and `passed()` checks for table presence, enabled countries, invalid coordinates, missing provider registrations, and site relationship registration. Evidence: `src/Health/AddressHealthCheck.php`, `src/Actions/BuildAddressQualityHealthReportAction.php`, `src/Providers/AddressServiceProvider.php`. - **M**

2. **Align manifest contributions with provider behavior.** The provider contributes Address/Country resources, configurators, language configurator, schema extender, and assets, but `capell.json contributes` is empty. Add manifest entries or tests that intentionally document why these are provider-only. Evidence: `capell.json`, `AddressServiceProvider::registerResources()`, `registerConfigurators()`, `registerSchemaExtenders()`. - **S**

3. **Add duplicate-address quality tooling.** The package now ships `FindDuplicateAddressGroupsAction`, `DuplicateAddressGroupData`, health report counts, and a dedicated duplicate-address diagnostic so likely duplicates are visible before any destructive merge behavior exists. Evidence: `src/Actions/FindDuplicateAddressGroupsAction.php`, `src/Data/DuplicateAddressGroupData.php`, `src/Actions/BuildAddressQualityHealthReportAction.php`, `src/Health/AddressHealthCheck.php`. - **Done**

4. **Document provider contract expectations.** Validation and geocoding providers are tagged extension points, but README/overview should show how a package registers one, what `isAvailable()` means, and how provider keys appear in the quality report. Evidence: `src/Contracts/AddressValidationProvider.php`, `src/Contracts/AddressGeocodingProvider.php`, `docs/address-api.md`. - **S**

## 3. Missing Features (gaps)

Capabilities declared: `address` and `address-admin`.

- **No health pass/fail method.** Marketplace health cannot tell whether address data quality is acceptable.
- **No dedupe workflow.** Shared foundational address data will drift without duplicate detection.
- **Done/Shipped: country dataset refresh path.** `capell:address-countries-import` imports JSON or CSV datasets with `name`, `iso2`, and `iso3`, supports dry-runs, restores soft-deleted ISO matches, and can disable enabled countries missing from an authoritative dataset.
- **No first-class privacy/export helpers.** Address records are PII and should have clear consuming-package export/erase guidance.

## 4. Issues / Risks

1. **Important gap: health is not actionable.** A report object is useful for tests, but installers need diagnostic labels and remediation. Recommended fix: add `runDiagnostics()`/`passed()` and tests. - **P2**

2. **Important gap: manifest traceability is weaker than provider behavior.** Empty `contributes` makes marketplace/install tooling under-report the admin/configurator surface. Recommended fix: update manifest metadata and tests. - **P2**

3. **Improvement: shared address records need duplicate detection before merge tooling.** Foundation data quality matters to every consuming package. Recommended fix: non-destructive duplicate report first. - **P3**

4. **Improvement: provider extension docs are thin.** Third-party validation/geocoding packages need exact tag and return-shape guidance. Recommended fix: docs and a fixture provider test. - **P3**

## 5. Marketplace & Positioning

Address is a foundational data package. For teams, the value is consistency: one address model reused across sites and packages. For developers, the value is model registration, reusable Filament components, site-scoped policies, and provider contracts for validation/geocoding.

**Current summary:** "Reusable countries and structured postal addresses for Capell: one shared address record, site-scoped, that any package can reference instead of re-modelling location fields."

**Improved summary:** "Shared country and postal-address infrastructure for Capell packages, with site-scoped admin management, validation/geocoding extension points, and reusable Filament fields."

**Media status:** Existing admin screenshots are useful and should stay promoted. Add data-quality/health screenshots only if a visible diagnostics surface is added.

**Cross-sell:** Equestrian Clinics, Bookings, Theme Local Services, Site settings, and any package that needs reusable location data.

## 6. Prioritized Roadmap

| Item                                                               | Bucket | Effort | Impact | Section ref |
| ------------------------------------------------------------------ | ------ | ------ | ------ | ----------- |
| Add actionable health diagnostics and pass/fail coverage           | Done   | M      | High   | §2.1, §4.1  |
| Align `capell.json` contributions with admin/configurator surfaces | Done   | S      | Medium | §2.2, §4.2  |
| Add non-destructive duplicate address quality report               | Done   | M      | Medium | §2.3, §4.3  |
| Document validation/geocoding provider registration contracts      | Done   | S      | Medium | §2.4, §4.4  |
| Add country dataset refresh/import command                         | Done   | M      | Medium | §3          |
| Add PII export/erase guidance for consuming packages               | Next   | S      | Medium | §3          |
| Add optional geocoding normalization workflow                      | Next   | M      | Medium | §3, §5      |
| Add safe merge workflow for duplicate addresses                    | Later  | L      | Medium | §3          |
| Add locale-specific address formatting profiles                    | Later  | M      | Medium | §5          |

## 7. Verification

Implementation slice 1 exposed the shipped admin resources, configurators, site schema extender, models, admin assets, migrations, console commands, and health check as manifest contributions. It also registered package migrations with Laravel Package Tools and made `AddressHealthCheck` return actionable diagnostics.

Implementation slice 2 added non-destructive duplicate-address quality reporting with normalized grouping by country, postal code, and address lines. Duplicate groups are surfaced through `BuildAddressQualityHealthReportAction`, `AddressHealthCheck::runDiagnostics()`, and package docs.

Implementation slice 3 added `ImportCountriesAction` and `capell:address-countries-import` for JSON/CSV ISO country refreshes with dry-run, restore, and disable-missing modes.

Verify with:

```bash
vendor/bin/pest packages/address/tests --configuration=phpunit.xml
```

For health or manifest changes, include:

```bash
vendor/bin/pest packages/address/tests/Unit/ManifestRequirementsTest.php packages/address/tests/Unit/AddressProviderContractsTest.php --configuration=phpunit.xml
```

## 8. Completion Checklist

- [x] Package plan created from current code, manifest, docs, screenshots, and tests.
- [x] Comprehensive local review pass completed for provider, health, models, policies, docs, and provider contracts.
- [x] Capell audience pass completed for package consumers and site operators.
- [x] Approved implementation slice 1 shipped: manifest contribution metadata, migration registration, and health diagnostics.
- [x] Approved implementation slice 2 shipped: non-destructive duplicate-address quality report and diagnostics.
- [x] Focused Address verification passed.
- [x] Package tests passed.
- [x] Repo preflight passed for changed files.
