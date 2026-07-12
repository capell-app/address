<?php

declare(strict_types=1);

namespace Capell\Address\Tests\Fixtures;

use Capell\Address\Contracts\AddressValidationProvider;
use Capell\Address\Data\AddressValidationResultData;
use Capell\Address\Models\Address;

final class FakeUnavailableAddressValidationProvider implements AddressValidationProvider
{
    public function key(): string
    {
        return 'unavailable-validation';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function validate(Address $address): AddressValidationResultData
    {
        return new AddressValidationResultData(
            provider: $this->key(),
            valid: false,
            messages: ['Provider is unavailable.'],
        );
    }
}
