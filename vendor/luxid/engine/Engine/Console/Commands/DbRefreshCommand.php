<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class DbRefreshCommand extends Command
{
    protected string $description = 'Refresh database (rollback & migrate)';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->line("🔄 Refreshing database...");
        $this->line("Implementation coming soon...");

        return 0;
    }
}
