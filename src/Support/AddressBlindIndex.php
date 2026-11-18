<?php

declare(strict_types=1);

namespace Capell\Address\Support;

use RuntimeException;

final class AddressBlindIndex
{
    public static function hash(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return hash_hmac('sha256', mb_strtolower(trim($value)), self::secret());
    }

    private static function secret(): string
    {
        $dedicated = config('capell-address.blind_index_key');

        if (is_string($dedicated) && $dedicated !== '') {
            return $dedicated;
        }

        $fallback = config('app.key');
        throw_unless(is_string($fallback) && $fallback !== '', RuntimeException::class, 'Address blind-index secret is unavailable.');

        return $fallback;
    }
}
