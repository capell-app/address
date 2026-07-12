<?php

declare(strict_types=1);

namespace Capell\Address\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;

final class AddressSiteSchemaExtenderContribution implements ExtensionContribution
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
