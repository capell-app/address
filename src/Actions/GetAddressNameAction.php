<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Address\Support\AddressSiteScope;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Resolves an address display name from its key for selected-option labels.
 *
 * @method static ?string run(?string $key)
 */
class GetAddressNameAction
{
    use AsFake;
    use AsObject;

    public function handle(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $name = AddressSiteScope::applyForCurrentActor(Address::query())
            ->whereKey($key)
            ->value('name');

        return is_string($name) ? $name : null;
    }
}
