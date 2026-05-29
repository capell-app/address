<?php

declare(strict_types=1);

namespace Capell\Address\Policies;

final class AddressPolicy extends AbstractAddressResourcePolicy
{
    protected static function subject(): string
    {
        return 'Address';
    }
}
