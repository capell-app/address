<?php

declare(strict_types=1);

namespace Capell\Address\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RunsExtensionMigration;

final class AddressMigrationsContribution implements ExtensionContribution, RunsExtensionMigration
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
