<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class DbResetCommand extends Command
{
    protected string $description = 'Reset database (drop & create)';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->warning("⚠️  This will reset the database (drop and recreate)!");

        if (!$this->confirm("Are you sure?", false)) {
            $this->line("Operation cancelled");
            return 0;
        }

        $this->line("🔄 Resetting database...");

        // Run drop and create
        // In practice, you'd call the actual methods
        $this->info("Reset functionality coming soon...");

        return 0;
    }
}
