<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

/** @method static int run(Model $subject) */
final class EraseAddressPrivacyDataAction
{
    use AsAction;

    public function handle(Model $subject): int
    {
        if (! $subject instanceof Site) {
            return 0;
        }

        $deleted = Address::query()->where('site_id', $subject->getKey())->delete();

        return is_int($deleted) ? $deleted : 0;
    }
}
