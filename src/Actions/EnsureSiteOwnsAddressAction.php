<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Core\Models\Site;
use Lorisleiva\Actions\Concerns\AsAction;

final class EnsureSiteOwnsAddressAction
{
    use AsAction;

    public function handle(Site $site): ?Address
    {
        $meta = is_array($site->meta) ? $site->meta : [];
        $addressId = $meta['address_id'] ?? null;
        if (! is_numeric($addressId)) {
            return null;
        }

        $address = Address::query()->find((int) $addressId);
        if (! $address instanceof Address) {
            return null;
        }

        $siteId = (int) $site->getKey();
        if ($address->site_id === null) {
            $address->forceFill(['site_id' => $siteId])->save();

            return $address;
        }
        if ($address->site_id === $siteId) {
            return $address;
        }

        $clone = $address->replicate();
        $clone->site_id = $siteId;
        $clone->save();
        $meta['address_id'] = $clone->getKey();
        $site->forceFill(['meta' => $meta])->saveQuietly();

        return $clone;
    }
}
