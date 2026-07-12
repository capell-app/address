<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Models\Address;
use Capell\Address\Support\AddressSiteScope;
use Illuminate\Support\Collection;
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
        $candidateLimit = max($limit, min(500, $limit * 10));
        $normalizedSearch = mb_strtolower(trim((string) $search));

        return AddressSiteScope::applyForCurrentActor(Address::query())
            ->with(['country'])
            ->limit($candidateLimit)
            ->ordered()
            ->get()
            ->when(
                $normalizedSearch !== '',
                static fn (Collection $addresses): Collection => $addresses->filter(
                    static fn (Address $address): bool => str_contains(
                        mb_strtolower($address->full_address . ' ' . (string) $address->name),
                        $normalizedSearch,
                    ),
                ),
            )
            ->take($limit)
            ->mapWithKeys(static fn (Address $address): array => [
                $address->getKey() => $fullAddressLabels ? $address->full_address : (string) $address->name,
            ])
            ->all();
    }
}
