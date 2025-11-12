<?php

declare(strict_types=1);

namespace Capell\Address\Enums;

use Capell\Address\Filament\Resources\Addresses\Schemas\Types\DefaultAddressSchema;

enum AddressSchemaEnum: string
{
    case Default = DefaultAddressSchema::class;
}
