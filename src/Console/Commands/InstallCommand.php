<?php

declare(strict_types=1);

namespace Capell\Address\Console\Commands;

use Capell\Address\Actions\InstallAddressPackageAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Install\ConsoleProgressReporter;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inserts address tables';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capell:address-install';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        InstallAddressPackageAction::run(CapellCore::getPackage('capell-app/address'), [], new ConsoleProgressReporter($this));

        $this->newLine();
        $this->info('Capell Address installed successfully.');

        return self::SUCCESS;
    }
}
