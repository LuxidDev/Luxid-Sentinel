<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;
use Luxid\Console\Application;

class HelpCommand extends Command
{
    protected string $description = 'Show help for commands';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $commandName = $this->args[0] ?? null;

        if ($commandName) {
            return $this->showCommandHelp($commandName);
        }

        return $this->showGeneralHelp();
    }

    private function showGeneralHelp(): int
    {
        $this->line("🍋 \033[1;36mJuice CLI - Luxid Framework Command Line Tool\033[0m");
        $this->line("");
        $this->line("\033[1;33mUsage:\033[0m");
        $this->line("  php juice [command] [options]");
        $this->line("");
        $this->line("\033[1;33mOptions:\033[0m");
        $this->line("  \033[1;32m--help, -h\033[0m    Display this help message");
        $this->line("  \033[1;32m--version, -V\033[0m Display version");
        $this->line("");
        $this->line("\033[1;33mCommands:\033[0m");

        $commands = [
            'Server' => ['start'],
            'Application' => ['fresh', 'status', 'routes', 'env:check', 'version'],
            'Database' => ['db:create', 'db:drop', 'db:reset', 'db:status', 'db:migrate', 'db:rollback', 'db:refresh'],
            'Make' => ['make:action', 'make:entity', 'make:middleware', 'make:migration', 'make:todo', 'make:api'],
            'Help' => ['help'],
        ];

        foreach ($commands as $category => $cmdList) {
            $this->line("\033[1;34m{$category}:\033[0m");
            foreach ($cmdList as $cmd) {
                $this->line("  \033[1;32m{$cmd}\033[0m");
            }
            $this->line("");
        }

        $this->line("\033[1;33mRun:\033[0m \033[1;32mphp juice help [command]\033[0m for command-specific help");

        return 0;
    }

    private function showCommandHelp(string $commandName): int
    {
        $helpTexts = [
            'start' => $this->getStartHelp(),
            'make:action' => $this->getMakeActionHelp(),
            'db:migrate' => $this->getDbMigrateHelp(),
            // Add more help texts as needed
        ];

        if (isset($helpTexts[$commandName])) {
            $this->line($helpTexts[$commandName]);
        } else {
            $this->line("\033[1;33mHelp for '{$commandName}':\033[0m");
            $this->line("No detailed help available for this command yet.");
            $this->line("Run \033[1;32mphp juice\033[0m to see available commands.");
        }

        return 0;
    }

    private function getStartHelp(): string
    {
        return <<<HELP
🍋 \033[1;36mphp juice start\033[0m

\033[1;33mDescription:\033[0m
  Start the Luxid development server

\033[1;33mUsage:\033[0m
  php juice start [options]

\033[1;33mOptions:\033[0m
  \033[1;32m--host=HOST\033[0m    Server host (default: localhost)
  \033[1;32m--port=PORT\033[0m    Server port (default: 8000)

\033[1;33mExamples:\033[0m
  php juice start
  php juice start --host=127.0.0.1 --port=8080

HELP;
    }

    private function getMakeActionHelp(): string
    {
        return <<<HELP
🍋 \033[1;36mphp juice make:action\033[0m

\033[1;33mDescription:\033[0m
  Create a new Action class

\033[1;33mUsage:\033[0m
  php juice make:action <name> [options]

\033[1;33mArguments:\033[0m
  \033[1;32mname\033[0m    The name of the Action (e.g., TodoAction or Users/ListAction)

\033[1;33mOptions:\033[0m
  \033[1;32m--force\033[0m    Overwrite if file exists

\033[1;33mExamples:\033[0m
  php juice make:action TodoAction
  php juice make:action Users/ListAction
  php juice make:action Api/UserAction --force

HELP;
    }

    private function getDbMigrateHelp(): string
    {
        return <<<HELP
🍋 \033[1;36mphp juice db:migrate\033[0m

\033[1;33mDescription:\033[0m
  Run database migrations

\033[1;33mUsage:\033[0m
  php juice db:migrate [options]

\033[1;33mOptions:\033[0m
  \033[1;32m--fresh\033[0m    Drop all tables and re-run migrations

\033[1;33mExamples:\033[0m
  php juice db:migrate
  php juice db:migrate --fresh

HELP;
    }
}
