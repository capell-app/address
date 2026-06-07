<?php

declare(strict_types=1);

namespace Capell\Address\Health;

use Capell\Address\Actions\BuildAddressQualityHealthReportAction;
use Capell\Address\Data\AddressQualityHealthReportData;
use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class AddressHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }

    public static function report(): AddressQualityHealthReportData
    {
        return BuildAddressQualityHealthReportAction::run();
    }
}
