<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Data\AddressRetentionResultData;
use Capell\Address\Models\Address;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/** @method static AddressRetentionResultData run(int $retentionDays, ?CarbonInterface $now = null, bool $dryRun = false) */
final class PruneAddressPrivacyDataAction
{
    use AsFake;
    use AsObject;

    public function handle(int $retentionDays, ?CarbonInterface $now = null, bool $dryRun = false): AddressRetentionResultData
    {
        throw_if($retentionDays <= 0, InvalidArgumentException::class, 'Address retention requires a positive number of days.');
        $cutoff = ($now === null ? CarbonImmutable::now() : CarbonImmutable::instance($now))->subDays($retentionDays);
        $matched = 0;
        $affected = 0;
        $held = 0;

        Address::onlyTrashed()->where('deleted_at', '<=', $cutoff)->eachById(static function (Address $address) use ($dryRun, &$matched, &$affected, &$held): void {
            if (data_get($address->meta, 'legal_hold') === true) {
                $held++;

                return;
            }

            $matched++;
            if (! $dryRun) {
                throw_unless($address->forceDelete() === true, RuntimeException::class, 'Address retention deletion was refused.');
                $affected++;
            }
        });

        return new AddressRetentionResultData($matched, $affected, $held);
    }
}
