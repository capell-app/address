<?php

declare(strict_types=1);

namespace Capell\Address\Support;

use Capell\Address\Models\Address;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Site;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class AddressSiteScope
{
    /**
     * @template TModel of Address
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function applyForCurrentActor(Builder $query): Builder
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable || SiteScope::isGlobalActor($actor)) {
            return $query;
        }

        $assignedSiteIds = $actor->getAssignedSiteIds();

        return $assignedSiteIds->isNotEmpty()
            ? $query->whereHas('sites', fn (Builder $siteQuery): Builder => $siteQuery->whereIn('sites.id', $assignedSiteIds))
            : $query->whereRaw('1 = 0');
    }

    public static function actorCanUseAddress(Authenticatable $actor, Address $address): bool
    {
        if (SiteScope::isGlobalActor($actor)) {
            return true;
        }

        $assignedSiteIds = $actor->getAssignedSiteIds();

        if ($assignedSiteIds->isEmpty()) {
            return false;
        }

        if ($address->relationLoaded('sites')) {
            $sites = $address->getRelation('sites');

            return $sites instanceof Collection
                && $sites->contains(fn (Site $site): bool => $assignedSiteIds->contains((int) $site->getKey()));
        }

        return $address->sites()
            ->whereIn('sites.id', $assignedSiteIds)
            ->exists();
    }
}
