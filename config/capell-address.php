<?php

declare(strict_types=1);

return [
    /*
     * Secret used to build the searchable blind indexes for encrypted address
     * lines and postal codes. Set a dedicated value so rotating APP_KEY does
     * not silently break address lookups. When unset, APP_KEY is used.
     *
     * After changing this value, run `php artisan capell:address-blind-index-rehash`
     * to recompute every stored index from the decrypted address values.
     */
    'blind_index_key' => env('CAPELL_ADDRESS_BLIND_INDEX_KEY'),

    'geocoding' => [
        /*
         * Maximum seconds to wait for a geocoding provider to connect, and in
         * total for the whole request. Every outbound geocoding request built
         * with BuildGeocodingHttpClientAction is bounded by these values. A
         * request that exceeds either is treated as a geocoding miss and the
         * address is left unchanged. Values are clamped to 1-30 seconds.
         */
        'connect_timeout' => (float) env('CAPELL_ADDRESS_GEOCODING_CONNECT_TIMEOUT', 3),
        'timeout' => (float) env('CAPELL_ADDRESS_GEOCODING_TIMEOUT', 5),
    ],
];
