<?php

declare(strict_types=1);

namespace Capell\Address\Actions;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Builds the HTTP client every geocoding provider must use for outbound
 * requests, with a bounded connect and total timeout from configuration and no
 * automatic retries, so a slow provider cannot hold up a batch or a request.
 *
 * @method static PendingRequest run()
 */
final class BuildGeocodingHttpClientAction
{
    use AsFake;
    use AsObject;

    public const float DEFAULT_CONNECT_TIMEOUT = 3.0;

    public const float DEFAULT_TIMEOUT = 5.0;

    private const float MINIMUM_SECONDS = 1.0;

    private const float MAXIMUM_SECONDS = 30.0;

    public function handle(): PendingRequest
    {
        $timeout = $this->seconds('capell-address.geocoding.timeout', self::DEFAULT_TIMEOUT);
        $connectTimeout = min(
            $this->seconds('capell-address.geocoding.connect_timeout', self::DEFAULT_CONNECT_TIMEOUT),
            $timeout,
        );

        return Http::connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->retry(0);
    }

    private function seconds(string $key, float $default): float
    {
        $value = config($key);

        if (! is_numeric($value) || (float) $value <= 0.0) {
            return $default;
        }

        return max(self::MINIMUM_SECONDS, min(self::MAXIMUM_SECONDS, (float) $value));
    }
}
