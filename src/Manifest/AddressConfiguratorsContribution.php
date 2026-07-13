<?php

declare(strict_types=1);

namespace Capell\Address\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;

final class AddressConfiguratorsContribution implements ExtensionContribution
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }
}
