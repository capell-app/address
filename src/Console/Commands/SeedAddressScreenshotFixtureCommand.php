<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Actions\SeedAddressScreenshotFixtureAction;
use Illuminate\Console\Command;
use Throwable;

final class SeedAddressScreenshotFixtureCommand extends Command
{
    protected $signature = 'capell:address-screenshot-fixture {--force : Confirm an intentional disposable screenshot seed}';

    protected $description = 'Seed Address record state for an explicit disposable screenshot run';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to seed screenshot fixtures without --force.');

            return self::FAILURE;
        }

        try {
            $counts = SeedAddressScreenshotFixtureAction::run();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Address screenshot fixture initialized with %d addresses.', $counts['addresses']));

        return self::SUCCESS;
    }
}
