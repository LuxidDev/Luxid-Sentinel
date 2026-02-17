<?php

namespace Luxid\Console;

use Luxid\Console\Commands\{
    StartCommand,
    FreshCommand,
    StatusCommand,
    RoutesCommand,
    DbCreateCommand,
    DbDropCommand,
    DbResetCommand,
    DbStatusCommand,
    MigrateCommand,
    RollbackCommand,
    DbRefreshCommand,
    MakeActionCommand,
    MakeEntityCommand,
    MakeMiddlewareCommand,
    MakeMigrationCommand,
    MakeApiCommand,
    EnvCheckCommand,
    VersionCommand,
    HelpCommand
};

class Application
{
    private array $commands = [];

    public function __construct()
    {
        $this->registerCommands();
    }

    private function registerCommands(): void
    {
        $this->commands = [
            'start' => StartCommand::class,
            'fresh' => FreshCommand::class,
            'status' => StatusCommand::class,
            'routes' => RoutesCommand::class,
            'db:create' => DbCreateCommand::class,
            'db:drop' => DbDropCommand::class,
            'db:reset' => DbResetCommand::class,
            'db:status' => DbStatusCommand::class,
            'db:migrate' => MigrateCommand::class,
            'db:rollback' => RollbackCommand::class,
            'db:refresh' => DbRefreshCommand::class,
            'make:action' => MakeActionCommand::class,
            'make:entity' => MakeEntityCommand::class,
            'make:middleware' => MakeMiddlewareCommand::class,
            'make:migration' => MakeMigrationCommand::class,
            'make:api' => MakeApiCommand::class,
            'env:check' => EnvCheckCommand::class,
            'version' => VersionCommand::class,
            'help' => HelpCommand::class,
        ];
    }

    public function run(?array $argv = null): int
    {
        $argv = $argv ?? $_SERVER['argv'];
        $commandName = $argv[1] ?? null;

        // Normailize common flags
        if (in_array($commandName, ['--version', '-V'], true)) {
            $commandName = 'version';
        }

        if (in_array($commandName, ['--help', '-h'], true)) {
            $commandName = 'help';
        }

        // Show interactive menu if no command
        if ($commandName === null) {
            return $this->showInteractiveMenu();
        }

        // Show help if command doesn't exist
        if (!isset($this->commands[$commandName])) {
            $this->error("❌ Command not found: {$commandName}");
            $this->line("");
            $this->showAvailableCommands();
            return 1;
        }

        $commandClass = $this->commands[$commandName];
        $command = new $commandClass();

        return $command->handle($argv);
    }

    private function showInteractiveMenu(): int
    {
        $this->header();
        $this->line("🌊 Welcome to Juice CLI - Luxid Framework");
        $this->line("");

        $menu = [
            ["🚀", "start", "Start development server"],
            ["🔄", "fresh", "Fresh install (clear, migrate, seed)"],
            ["📊", "status", "Check application status"],
            ["🛣️", "routes", "List all routes"],
            ["", "", ""],
            ["🗄️", "db:*", "Database operations"],
            ["⚡", "make:*", "Generate code"],
            ["🔧", "env:check", "Validate environment"],
            ["ℹ️", "version", "Show version"],
            ["❓", "help", "Show help"],
        ];

        foreach ($menu as $item) {
            if ($item[1] === '') {
                $this->line("");
                continue;
            }
            $this->line("  {$item[0]}  \033[1;33m{$item[1]}\033[0m");
            $this->line("      {$item[2]}");
        }

        $this->line("");
        $this->line("💡 Tip: Use \033[1;32mphp juice help [command]\033[0m for detailed help");
        $this->line("");

        return 0;
    }

    private function showAvailableCommands(): void
    {
        $this->line("📋 Available commands:");
        $this->line("");

        $categories = [
            'Server' => ['start'],
            'Application' => ['fresh', 'status', 'routes', 'env:check', 'version'],
            'Database' => array_filter(array_keys($this->commands), fn($c) => str_starts_with($c, 'db:')),
            'Make' => array_filter(array_keys($this->commands), fn($c) => str_starts_with($c, 'make:')),
            'Help' => ['help'],
        ];

        foreach ($categories as $category => $commands) {
            if (empty($commands)) continue;

            $this->line("\033[1;34m{$category}:\033[0m");
            foreach ($commands as $command) {
                $commandClass = $this->commands[$command];
                $commandInstance = new $commandClass();
                $description = $commandInstance->getDescription();

                $this->line("  \033[1;32m{$command}\033[0m - {$description}");
            }
            $this->line("");
        }
    }

    private function header(): void
    {
        $width = $this->getTerminalWidth();
        $padding = str_repeat(" ", max(0, ($width - 40) / 2));

        $this->line("\033[1;36m{$padding}╔══════════════════════════════════════════╗\033[0m");
        $this->line("\033[1;36m{$padding}║           🍋 Juice CLI v1.0             ║\033[0m");
        $this->line("\033[1;36m{$padding}║           🍋 Juice CLI v1.0             ║\033[0m");
            $this->line("\033[1;36m{$padding}╚══════════════════════════════════════════╝\033[0m");
        }

        private function getTerminalWidth(): int
        {
            return 80; // Default width
        }

        private function error(string $message): void
        {
            echo "\033[31m{$message}\033[0m" . PHP_EOL;
        }

        private function line(string $message): void
        {
            echo $message . PHP_EOL;
        }
    }
