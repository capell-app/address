<?php

declare(strict_types=1);

namespace Capell\Address\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionAsset;

final class AddressAdminAssetsContribution implements ExtensionContribution, RegistersExtensionAsset
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }
}
