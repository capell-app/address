<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static array<string, mixed> run(Model $subject) */
final class BuildAddressPrivacyExportAction
{
    use AsFake;
    use AsObject;

    /** @return array<string, mixed> */
    public function handle(Model $subject): array
    {
        if (! $subject instanceof Site) {
            return [];
        }

        return ['addresses' => Address::query()->where('site_id', $subject->getKey())->get()->map->attributesToArray()->all()];
    }
}
