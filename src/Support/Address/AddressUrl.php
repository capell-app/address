<?php

declare(strict_types=1);

namespace Capell\Address\Support\Address;

use Capell\Address\Models\Address;

class AddressUrl
{
    public static function url(Address $address): ?string
    {
        $addressString = str_replace(' ', '+', $address->full_address);

        if (! empty($address->meta['latitude']) && ! empty($address->meta['longitude'])) {
            $addressString .= '@' . $address->meta['latitude'] . ',' . $address->meta['longitude'];
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . $addressString;
    }
}
