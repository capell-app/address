<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Address\Support\AddressSiteScope;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Builds address selector options for admin forms.
 *
 * @method static array<int, string> run(?string $search, int $limit, bool $fullAddressLabels = false)
 */
class ListAddressOptionsAction
{
    use AsObject;

    /**
     * @return array<int, string>
     */
    public function handle(?string $search, int $limit, bool $fullAddressLabels = false): array
    {
        return AddressSiteScope::applyForCurrentActor(Address::query())
            ->when(
                $search !== null && $search !== '',
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query->where('line1', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('line2', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('city', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('state', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('postal_code', 'like', sprintf('%%%s%%', $search))
                        ->orWhereRelation('country', 'name', 'like', sprintf('%%%s%%', $search)),
                ),
            )
            ->with(['country'])
            ->limit($limit)
            ->ordered()
            ->get()
            ->mapWithKeys(static fn (Address $address): array => [
                $address->getKey() => $fullAddressLabels ? $address->full_address : (string) $address->name,
            ])
            ->all();
    }
}
