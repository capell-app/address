<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Lorisleiva\Actions\Concerns\AsAction;

final class CloneSharedAddressForMutationAction
{
    use AsAction;

    /** @param array<string, mixed> $attributes */
    public function handle(Address $address, array $attributes, ?int $siteId = null): Address
    {
        if ($address->sites()->count() > 1 || ($address->site_id !== null && $siteId !== null && $address->site_id !== $siteId)) {
            $clone = $address->replicate();
            $clone->site_id = $siteId;
            $clone->fill($attributes)->save();

            return $clone;
        }

        $address->site_id ??= $siteId;
        $address->fill($attributes)->save();

        return $address;
    }
}
