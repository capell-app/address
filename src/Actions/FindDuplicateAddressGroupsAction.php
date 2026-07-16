<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Capell\Address\Data\DuplicateAddressGroupData;
use Capell\Address\Models\Address;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Collection<int, DuplicateAddressGroupData> run(iterable<int, Address>|null $addresses = null)
 */
final class FindDuplicateAddressGroupsAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  iterable<int, Address>|null  $addresses
     * @return Collection<int, DuplicateAddressGroupData>
     */
    public function handle(?iterable $addresses = null): Collection
    {
        /**
         * @var array<string, array{
         *     key: string,
         *     countryId: int|null,
         *     postalCode: string,
         *     line1: string,
         *     line2: string|null,
         *     addressIds: list<int>
         * }> $groups
         */
        $groups = [];

        foreach ($this->addressRows($addresses) as $address) {
            $key = $this->duplicateKey($address);

            if (! array_key_exists($key, $groups)) {
                $groups[$key] = [
                    'key' => $key,
                    'countryId' => $address->country_id,
                    'postalCode' => $address->postal_code ?? '',
                    'line1' => $address->line1 ?? '',
                    'line2' => $address->line2,
                    'addressIds' => [],
                ];
            }

            if ($address->getKey() !== null) {
                $addressId = $address->getKey();

                if (is_numeric($addressId)) {
                    $groups[$key]['addressIds'][] = (int) $addressId;
                }
            }
        }

        return collect($groups)
            ->filter(static fn (array $group): bool => count($group['addressIds']) > 1)
            ->map(static fn (array $group): DuplicateAddressGroupData => new DuplicateAddressGroupData(
                key: $group['key'],
                countryId: $group['countryId'],
                postalCode: $group['postalCode'],
                line1: $group['line1'],
                line2: $group['line2'],
                count: count($group['addressIds']),
                addressIds: $group['addressIds'],
            ))
            ->sort(static fn (DuplicateAddressGroupData $first, DuplicateAddressGroupData $second): int => ($second->count <=> $first->count)
                ?: ($first->key <=> $second->key))
            ->values();
    }

    /**
     * @param  iterable<int, Address>|null  $addresses
     * @return iterable<int, Address>
     */
    private function addressRows(?iterable $addresses): iterable
    {
        if ($addresses !== null) {
            return $addresses;
        }

        return Address::query()
            ->select(['id', 'country_id', 'line1', 'line2', 'postal_code'])
            ->cursor();
    }

    private function duplicateKey(Address $address): string
    {
        return implode('|', [
            (string) ($address->country_id ?? 'missing'),
            $this->normalizePostalCode($address->postal_code),
            $this->normalizeAddressPart($address->line1),
            $this->normalizeAddressPart($address->line2),
        ]);
    }

    private function normalizePostalCode(?string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', strtolower(trim((string) $value)));
    }

    private function normalizeAddressPart(?string $value): string
    {
        $normalized = (string) preg_replace('/[^a-z0-9]+/', ' ', strtolower(trim((string) $value)));

        return (string) preg_replace('/\s+/', ' ', trim($normalized));
    }
}
