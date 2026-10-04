# Extension and action examples

<!-- Maintained by scripts/generate-package-readmes.php -->

Use the action functions with records and Data objects supplied by your application.
They pass each argument to the package operation and return its result.

These adapters show container registration. Use the owning package registry when
a contract requires contributor discovery.

Contract adapters wrap an existing implementation. Call their registration function
from your service provider with that implementation; tagged contracts keep their declared tag.
Resolve the backend by its concrete class before registration so the replacement contract
does not resolve itself. Static contract metadata uses one backend class per adapter.

## Contract `Capell\Address\Contracts\AddressGeocodingProvider`

<!-- example: contract Capell\Address\Contracts\AddressGeocodingProvider -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\Address;

final class AddressGeocodingProviderAdapter implements \Capell\Address\Contracts\AddressGeocodingProvider
{
    public function __construct(private readonly \Capell\Address\Contracts\AddressGeocodingProvider $backend) {}

    #[\Override]
    public function geocode(\Capell\Address\Models\Address $address): \Capell\Address\Data\AddressGeocodingResultData
    {
        return $this->backend->geocode($address);
    }

    #[\Override]
    public function isAvailable(): bool
    {
        return $this->backend->isAvailable();
    }

    #[\Override]
    public function key(): string
    {
        return $this->backend->key();
    }
}

function registerAddressGeocodingProviderAdapter(\Capell\Address\Contracts\AddressGeocodingProvider $backend): void
{
    app()->bind(AddressGeocodingProviderAdapter::class, static fn (): AddressGeocodingProviderAdapter => new AddressGeocodingProviderAdapter($backend));
    app()->bind(\Capell\Address\Contracts\AddressGeocodingProvider::class, AddressGeocodingProviderAdapter::class);
    app()->tag([AddressGeocodingProviderAdapter::class], \Capell\Address\Contracts\AddressGeocodingProvider::TAG);
}
```

## Contract `Capell\Address\Contracts\AddressValidationProvider`

<!-- example: contract Capell\Address\Contracts\AddressValidationProvider -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\Address;

final class AddressValidationProviderAdapter implements \Capell\Address\Contracts\AddressValidationProvider
{
    public function __construct(private readonly \Capell\Address\Contracts\AddressValidationProvider $backend) {}

    #[\Override]
    public function isAvailable(): bool
    {
        return $this->backend->isAvailable();
    }

    #[\Override]
    public function key(): string
    {
        return $this->backend->key();
    }

    #[\Override]
    public function validate(\Capell\Address\Models\Address $address): \Capell\Address\Data\AddressValidationResultData
    {
        return $this->backend->validate($address);
    }
}

function registerAddressValidationProviderAdapter(\Capell\Address\Contracts\AddressValidationProvider $backend): void
{
    app()->bind(AddressValidationProviderAdapter::class, static fn (): AddressValidationProviderAdapter => new AddressValidationProviderAdapter($backend));
    app()->bind(\Capell\Address\Contracts\AddressValidationProvider::class, AddressValidationProviderAdapter::class);
    app()->tag([AddressValidationProviderAdapter::class], \Capell\Address\Contracts\AddressValidationProvider::TAG);
}
```

## Action `install`

<!-- example: action install -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\Address;

function runInstall(\Capell\Core\Data\PackageData $package, array $arguments = [], ?\Capell\Core\Contracts\ProgressReporter $reporter = null): void
{
    \Capell\Address\Actions\InstallAddressPackageAction::run($package, $arguments, $reporter);
}
```
