<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class StartCommand extends Command
{
    protected string $description = 'Start the development server';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->line("🚀 Starting Luxid development server...");
        $this->line("");

        $host = $this->options['host'] ?? 'localhost';
        $port = $this->options['port'] ?? 8000;
        $webDir = $this->getProjectRoot() . '/web';

        $this->line("🌐 Server running at: \033[1;34mhttp://{$host}:{$port}\033[0m");
        $this->line("📁 Serving from: {$webDir}");
        $this->line("🛑 Press \033[1;31mCtrl+C\033[0m to stop");
        $this->line("");
        $this->line("\033[33mStarting PHP built-in server...\033[0m");

        // Start the server
        passthru("php -S {$host}:{$port} -t {$webDir}");

        return 0;
    }
}
