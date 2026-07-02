<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Builds the country option list for the country selector, optionally filtered
 * by a search term matching name, ISO-2, or ISO-3. Returns names keyed by id.
 *
 * @method static array<int, string> run(?string $search, int $limit)
 */
class ListCountryOptionsAction
{
    use AsObject;

    /**
     * @return array<int, string>
     */
    public function handle(?string $search, int $limit): array
    {
        /** @var class-string<Country> $model */
        $model = Country::class;

        return $model::query()
            ->when(
                $search !== null && $search !== '',
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('iso2', 'like', $search)
                        ->orWhere('iso3', 'like', $search),
                ),
            )
            ->limit($limit)
            ->ordered()
            ->get()
            ->mapWithKeys(fn (Country $country): array => [$country->id => $country->name])
            ->all();
    }
}
