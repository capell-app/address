<?php

declare(strict_types=1);

namespace Capell\Address\Tests\Fixtures;

use Capell\Address\Contracts\AddressValidationProvider;
use Capell\Address\Data\AddressValidationResultData;
use Capell\Address\Models\Address;

final class FakeAvailableAddressValidationProvider implements AddressValidationProvider
{
    public function key(): string
    {
        return 'fixture-validation';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function validate(Address $address): AddressValidationResultData
    {
        return new AddressValidationResultData(
            provider: $this->key(),
            valid: $address->line1 !== null,
            confidence: 0.9,
            messages: [],
            normalized: [
                'line1' => $address->line1,
                'postal_code' => $address->postal_code,
            ],
        );
    }
}
