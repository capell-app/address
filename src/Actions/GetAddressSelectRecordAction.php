<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Address\Support\AddressSiteScope;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Resolves the selected address record for admin address selectors.
 *
 * @method static Address run(int $key)
 */
class GetAddressSelectRecordAction
{
    use AsFake;
    use AsObject;

    public function handle(int $key): Address
    {
        return AddressSiteScope::applyForCurrentActor(Address::query())
            ->whereKey($key)
            ->firstOrFail();
    }
}
