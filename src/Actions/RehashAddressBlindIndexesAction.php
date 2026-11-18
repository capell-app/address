<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Address\Support\AddressBlindIndex;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Recomputes the stored blind indexes from decrypted address values with the
 * currently configured blind-index key, and returns how many addresses changed.
 */
final class RehashAddressBlindIndexesAction
{
    use AsFake;
    use AsObject;

    public function handle(int $chunkSize = 200): int
    {
        $changed = 0;

        Address::query()
            ->withTrashed()
            ->chunkById(max(1, $chunkSize), function (Collection $addresses) use (&$changed): void {
                foreach ($addresses as $address) {
                    if (! $address instanceof Address) {
                        continue;
                    }

                    $line1Hash = AddressBlindIndex::hash($address->line1);
                    $postalCodeHash = AddressBlindIndex::hash($address->postal_code);

                    if ($address->getRawOriginal('line1_hash') === $line1Hash
                        && $address->getRawOriginal('postal_code_hash') === $postalCodeHash) {
                        continue;
                    }

                    Address::query()
                        ->withTrashed()
                        ->whereKey($address->getKey())
                        ->toBase()
                        ->update([
                            'line1_hash' => $line1Hash,
                            'postal_code_hash' => $postalCodeHash,
                        ]);

                    $changed++;
                }
            });

        return $changed;
    }
}
