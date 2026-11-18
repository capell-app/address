# Address API

Address owns the shared `Address` and `Country` models for Capell packages. Consuming packages should reference those models and extension contracts instead of adding package-specific country tables, ad hoc location columns, or hidden geocoding state.

## Provider Contracts

Validation and geocoding are optional extension points. A package that integrates a third-party service should implement one or both contracts, bind the implementation in its service provider, and tag the binding with the contract tag constant.

| Need                                     | Contract                                             | Tag                                                       |
| ---------------------------------------- | ---------------------------------------------------- | --------------------------------------------------------- |
| Validate and normalise postal addresses  | `Capell\Address\Contracts\AddressValidationProvider` | `Capell\Address\Contracts\AddressValidationProvider::TAG` |
| Resolve coordinates for stored addresses | `Capell\Address\Contracts\AddressGeocodingProvider`  | `Capell\Address\Contracts\AddressGeocodingProvider::TAG`  |

```php
use Capell\Address\Contracts\AddressValidationProvider;

public function register(): void
{
    $this->app->bind(
        'vendor.address.validation',
        VendorAddressValidationProvider::class,
    );

    $this->app->tag(
        ['vendor.address.validation'],
        AddressValidationProvider::TAG,
    );
}
```

Use a stable `key()` value such as `vendor-validation` or `vendor-geocoding`. Address quality reports expose provider keys, not service container binding names, so the key should be safe to show in diagnostics and support output.

## Availability

`isAvailable()` should return `true` only when the provider is configured enough to run in the current application. Typical checks are API credentials, enabled feature flags, configured regions, or required PHP extensions.

Unavailable providers are ignored by `BuildAddressQualityHealthReportAction` and `AddressHealthCheck`. This lets a package ship optional integrations without turning an unconfigured API key into a failed install health check.

## Result Shape

`AddressValidationProvider::validate()` returns `AddressValidationResultData`:

- `provider`: the same stable key returned by `key()`.
- `valid`: whether the address passed provider validation.
- `confidence`: optional score from `0.0` to `1.0` when the provider exposes one.
- `messages`: safe diagnostic strings for logs or admin support surfaces.
- `normalized`: provider-normalised fields such as `line1`, `line2`, `city`, `postal_code`, or country code.

`AddressGeocodingProvider::geocode()` returns `AddressGeocodingResultData`:

- `provider`: the same stable key returned by `key()`.
- `latitude` and `longitude`: string coordinates, or `null` when the provider cannot resolve them.
- `confidence`: optional score from `0.0` to `1.0`.
- `messages`: safe diagnostic strings for logs or admin support surfaces.

Do not include raw API secrets, signed URLs, provider request payloads, model IDs from another package, or editor/admin-only details in result messages. Public rendering must remain clean even when a consuming package displays address data.

## Quality Reports

`BuildAddressQualityHealthReportAction::run()` includes two provider lists:

- `validationProviders`: keys from tagged validation providers where `isAvailable()` returns `true`.
- `geocodingProviders`: keys from tagged geocoding providers where `isAvailable()` returns `true`.

Provider absence is not a health failure. Address health fails for data quality problems such as missing enabled countries or invalid coordinates, and warns for likely duplicate address groups. Optional providers make the report more informative without becoming a required dependency.

## Geocoding Normalisation

`NormalizeAddressGeocodingAction::run($address, providerKey: null, dryRun: false)` calls the first available tagged geocoding provider that returns valid latitude and longitude values. When coordinates change, Address stores `latitude`, `longitude`, `geocoding_provider`, `geocoding_confidence`, and `geocoded_at` in address metadata.

Operators can run `capell:address-geocode-normalize` to normalise batches. Use `--dry-run` to count changes without writing, `--limit=100` to cap a batch, and `--provider=provider-key` to force one available provider. The command is optional infrastructure: if no geocoding providers are tagged and available, it scans safely and writes nothing.

## Lawful Basis for Geocoding

Capell ships no geocoding HTTP provider. A provider that sends an address to an external service discloses personal data to a third party. The site operator decides and records the lawful basis for that disclosure, and tells the people concerned, before enabling the provider. Capell does not choose the basis. The command `capell:address-geocode-normalize` is opt-in: it does nothing until a provider is registered and an operator runs it.

A provider author documents what the provider sends, where it sends it and how long the third party keeps it, so operators can record the basis and update their privacy notice.

## Geocoding Timeouts

Geocoding providers that call an external service must build their HTTP client with `Capell\Address\Actions\BuildGeocodingHttpClientAction::run()`. The client sets a connect timeout and a total timeout, and does not retry, so a slow provider cannot stall a batch.

| Config key                                 | Environment variable                       | Default | Meaning                                                                  |
| ------------------------------------------ | ------------------------------------------ | ------- | ------------------------------------------------------------------------ |
| `capell-address.geocoding.connect_timeout` | `CAPELL_ADDRESS_GEOCODING_CONNECT_TIMEOUT` | 3       | Seconds to wait for the connection. Never longer than the total timeout. |
| `capell-address.geocoding.timeout`         | `CAPELL_ADDRESS_GEOCODING_TIMEOUT`         | 5       | Seconds to wait for the whole request.                                   |

Values are clamped to 1-30 seconds; a non-numeric or non-positive value falls back to the default. If a provider throws, including on a timeout, `NormalizeAddressGeocodingAction` logs the provider key and exception class, leaves the address unchanged and tries the next provider. A failed address counts as scanned but not updated, and the batch continues.

## Privacy Export And Erasure Guidance

Postal addresses are personal data when they identify a person, household, order, booking, attendee, customer, or site contact. Consuming packages own the subject relationship, so they should include address fields in their own subject export rather than asking Address to guess which person owns a shared record.

For subject exports, include the address values that were used by the consuming record: `line1`, `line2`, `city`, `county`, `postal_code`, country `name`, `iso2`, `iso3`, and relevant location metadata. Treat latitude and longitude as precise location data. Do not include provider API payloads, validation request bodies, unrelated address records, admin-only model IDs, or internal duplicate-quality diagnostics in customer-facing exports.

For erasure, first decide whether the address is exclusive to the subject being erased. If it is exclusive, the consuming package may delete, anonymize, or null the address relationship according to its retention policy. If the address is shared by another customer, site, booking, invoice, legal record, or operational workflow, detach the relationship instead of deleting a shared address. Countries are reference data and should not be deleted for subject erasure.

Validation and geocoding providers send address data to third parties, so the site operator records the lawful basis before enabling one (see Lawful Basis for Geocoding). Providers should keep export/erase workflows simple: store only normalised address fields and safe diagnostic messages in Address records, and keep raw provider payloads in the owning package only when that package has an explicit retention and erasure policy.
