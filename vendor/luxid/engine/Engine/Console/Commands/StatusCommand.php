<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class StatusCommand extends Command
{
    protected string $description = 'Check application status';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->line("📊 \033[1;36mApplication Status\033[0m");
        $this->line(str_repeat("─", 50));

        // Project info
        $this->line("📁 \033[1;33mRoot:\033[0m " . $this->getProjectRoot());

        // Environment
        $envFile = $this->getProjectRoot() . '/.env';
        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);
            $this->line("🌐 \033[1;33mEnvironment:\033[0m " . ($env['APP_ENV'] ?? 'unknown'));
        } else {
            $this->line("🌐 \033[1;31mEnvironment:\033[0m .env file not found");
        }

        // Directories
        $this->checkDirectory('app', 'App directory');
        $this->checkDirectory('config', 'Config directory');
        $this->checkDirectory('migrations', 'Migrations directory');
        $this->checkDirectory('routes', 'Routes directory');
        $this->checkDirectory('web', 'Web directory');

        // Routes
        $routesFile = $this->getRoutesPath() . '/api.php';
        if (file_exists($routesFile)) {
            $routeCount = $this->countRoutes($routesFile);
            $this->line("🛣️  \033[1;33mRoutes:\033[0m {$routeCount} registered");
        } else {
            $this->line("🛣️  \033[1;31mRoutes:\033[0m routes/api.php not found");
        }

        // Migrations
        $migrationsPath = $this->getMigrationsPath();
        if (is_dir($migrationsPath)) {
            $migrations = array_filter(scandir($migrationsPath), function($file) {
                return $file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'php';
            });
            $this->line("🗄️  \033[1;33mMigrations:\033[0m " . count($migrations) . " files");
        }

        // PHP version
        $this->line("🐘 \033[1;33mPHP:\033[0m " . PHP_VERSION);

        // Memory
        $memory = memory_get_usage(true) / 1024 / 1024;
        $this->line("💾 \033[1;33mMemory:\033[0m " . round($memory, 2) . " MB");

        $this->line(str_repeat("─", 50));
        $this->line("⚡ \033[1;32mStatus: Ready\033[0m");

        return 0;
    }

    private function checkDirectory(string $dir, string $label): void
    {
        $path = $this->getProjectRoot() . '/' . $dir;
        if (is_dir($path)) {
            $this->line("📂 \033[1;32m{$label}:\033[0m ✓ Found");
        } else {
            $this->line("📂 \033[1;31m{$label}:\033[0m ✗ Missing");
        }
    }

    private function countRoutes(string $filePath): int
    {
        if (!file_exists($filePath)) return 0;

        $content = file_get_contents($filePath);
        $pattern = '/\$router->(get|post|put|patch|delete)\(/';

        return preg_match_all($pattern, $content);
    }
}
