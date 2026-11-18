<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/** @method static int run(Model $subject) */
final class EraseAddressPrivacyDataAction
{
    use AsFake;
    use AsObject;

    public function handle(Model $subject): int
    {
        if (! $subject instanceof Site) {
            return 0;
        }

        $deleted = 0;
        Address::withTrashed()->where('site_id', $subject->getKey())->eachById(static function (Address $address) use (&$deleted): void {
            throw_unless($address->forceDelete() === true, RuntimeException::class, 'Address erasure was refused.');
            $deleted++;
        });

        return $deleted;
    }
}
