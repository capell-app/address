<?php

declare(strict_types=1);

namespace Capell\Address\Policies;

use Capell\Address\Models\Address;
use Capell\Address\Support\AddressSiteScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Override;

final class AddressPolicy extends AbstractAddressResourcePolicy
{
    #[Override]
    public function view(User $user, Model $record): bool
    {
        return parent::view($user, $record)
            && $record instanceof Address
            && AddressSiteScope::actorCanUseAddress($user, $record);
    }

    #[Override]
    public function update(User $user, Model $record): bool
    {
        return parent::update($user, $record)
            && $record instanceof Address
            && AddressSiteScope::actorCanUseAddress($user, $record);
    }

    #[Override]
    public function delete(User $user, Model $record): bool
    {
        return parent::delete($user, $record)
            && $record instanceof Address
            && AddressSiteScope::actorCanUseAddress($user, $record);
    }

    #[Override]
    public function restore(User $user, Model $record): bool
    {
        return parent::restore($user, $record)
            && $record instanceof Address
            && AddressSiteScope::actorCanUseAddress($user, $record);
    }

    #[Override]
    public function forceDelete(User $user, Model $record): bool
    {
        return parent::forceDelete($user, $record)
            && $record instanceof Address
            && AddressSiteScope::actorCanUseAddress($user, $record);
    }

    #[Override]
    public function replicate(User $user, Model $record): bool
    {
        return parent::replicate($user, $record)
            && $record instanceof Address
            && AddressSiteScope::actorCanUseAddress($user, $record);
    }

    protected static function subject(): string
    {
        return 'Address';
    }
}
