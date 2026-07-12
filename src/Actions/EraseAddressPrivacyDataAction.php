<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

final class EraseAddressPrivacyDataAction
{
    use AsAction;

    public function handle(Model $subject): int
    {
        return $subject instanceof Site ? Address::query()->where('site_id', $subject->getKey())->delete() : 0;
    }
}
