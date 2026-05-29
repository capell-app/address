<?php

declare(strict_types=1);

namespace Capell\Address\Contracts;

use Capell\Address\Data\AddressValidationResultData;
use Capell\Address\Models\Address;

interface AddressValidationProvider
{
    public const string TAG = 'capell-address:validation-providers';

    public function key(): string;

    public function isAvailable(): bool;

    public function validate(Address $address): AddressValidationResultData;
}
