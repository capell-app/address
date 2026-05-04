<?php

declare(strict_types=1);

use Capell\Address\Tests\AddressTestCase;

pest()->extend(AddressTestCase::class)->group('address')->in(__DIR__);
