<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DemoCommand extends Command
{
    protected $description = 'Inserts demo address content into the selected site(s).';

    protected $signature = 'capell:address-demo {--sites=}';

    public function handle(): int
    {
        $siteNames = $this->resolveSiteNames();
        $address = $this->setupAddress();

        $sites = $this->resolveSites($siteNames);

        if ($sites->isEmpty()) {
            if ($siteNames !== null) {
                $this->error('Unable to find any sites for: ' . implode(', ', $siteNames));

                return Command::FAILURE;
            }

            $this->warn('No sites found. Created reusable demo address content without linking it to a site.');
            $this->newLine();
            $this->info('Address demo content inserted successfully.');

            return Command::SUCCESS;
        }

        $sites->each(function (Site $site) use ($address): void {
            $this->newLine();
            $this->line(sprintf('Selected site: %s', $site->name));

            $meta = $site->meta ?? [];

            $meta['address_id'] = $address->id;
            $site->meta = $meta;
            $site->save();

            $this->line('Demo address content has been successfully created for site: ' . $site->name);
        });

        $this->newLine();
        $this->info('Address demo content inserted successfully.');

        return Command::SUCCESS;
    }

    private function setupCountry(): Country
    {
        /** @var class-string<Country> $countryModel */
        $countryModel = Country::class;

        /** @var class-string<Language> $model */
        $model = Language::class;

        return $countryModel::query()->firstOrCreate(['iso2' => 'US'], [
            'name' => 'United States',
            'iso2' => 'US',
            'iso3' => 'USA',
            'language_id' => $model::query()->where('code', 'en')->first()->id,
        ]);
    }

    private function setupAddress(): Address
    {
        /** @var class-string<Address> $model */
        $model = Address::class;

        /** @var Address $address */
        $address = $model::query()->firstOrCreate([
            'line1' => '123 Main St',
            'city' => 'Anytown',
            'postal_code' => '12345',
            'country_id' => $this->setupCountry()->id,
        ], [
            'name' => 'Headquarters',
            'line2' => 'Suite 100',
            'state' => 'CA',
            'meta' => [
                'latitude' => 34.0522,
                'longitude' => -118.2437,
            ],
        ]);

        return $address;
    }

    /**
     * @param  array<int, string>|null  $siteNames
     * @return Collection<int, Site>
     */
    private function resolveSites(?array $siteNames): Collection
    {
        /** @var Collection<int, Site> $sites */
        $sites = Site::query()
            ->with(['language', 'languages'])
            ->when(
                $siteNames !== null,
                fn (Builder $query): Builder => $query->whereIn('name', $siteNames),
            )
            ->get();

        return $sites;
    }

    /**
     * @return array<int, string>|null
     */
    private function resolveSiteNames(): ?array
    {
        $sitesOption = $this->option('sites');

        if (is_string($sitesOption) && $sitesOption !== '') {
            return array_values(array_filter(
                array_map(trim(...), explode(',', $sitesOption)),
                static fn (string $site): bool => $site !== '',
            ));
        }

        if (is_array($sitesOption)) {
            return array_values(array_filter(
                array_map(static fn (mixed $site): string => trim((string) $site), $sitesOption),
                static fn (string $site): bool => $site !== '',
            ));
        }

        return null;
    }
}
