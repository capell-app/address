<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Country;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Resolves a country's display name from its key, for the selector's
 * selected-option label.
 *
 * @method static ?string run(?string $key)
 */
class GetCountryNameAction
{
    use AsObject;

    public function handle(?string $key): ?string
    {
        /** @var class-string<Country> $model */
        $model = Country::class;

        return $model::query()
            ->whereKey($key)
            ->first(['name'])?->name;
    }
}
