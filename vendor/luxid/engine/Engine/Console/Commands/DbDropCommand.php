<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class DbDropCommand extends Command
{
    protected string $description = 'Drop the database';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->warning("⚠️  This will drop the entire database!");

        if (!$this->confirm("Are you sure you want to drop the database?", false)) {
            $this->line("Operation cancelled");
            return 0;
        }

        $this->line("🗑️  Dropping database...");
        $this->line("Implementation coming soon...");

        return 0;
    }
}
