<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class FreshCommand extends Command
{
    protected string $description = 'Fresh install (clear cache, run migrations, seed)';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->line("🧹 Starting fresh installation...");

        $steps = [
            'Clear cache' => fn() => $this->clearCache(),
            'Drop database' => fn() => $this->dropDatabase(),
            'Create database' => fn() => $this->createDatabase(),
            'Run migrations' => fn() => $this->runMigrations(),
            'Run seeders' => fn() => $this->runSeeders(),
        ];

        $total = count($steps);
        $current = 1;

        foreach ($steps as $label => $action) {
            $this->line("");
            $this->line("📦 Step {$current}/{$total}: {$label}");
            try {
                $action();
                $this->line("  \033[32m✓ Completed\033[0m");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: " . $e->getMessage());
                return 1;
            }
            $current++;
        }

        $this->line("");
        $this->success("Fresh installation completed successfully!");
        $this->line("🚀 Your application is ready!");

        return 0;
    }

    private function clearCache(): void
    {
        $cachePath = $this->getProjectRoot() . '/cache';
        if (is_dir($cachePath)) {
            $this->removeDirectory($cachePath);
        }
        mkdir($cachePath, 0755, true);
        $this->info("Cache cleared");
    }

    private function dropDatabase(): void
    {
        $this->warning("Dropping database...");
        // This would require database connection setup
        $this->line("  Skipping (database connection required)");
    }

    private function createDatabase(): void
    {
        $this->info("Creating database...");
        // This would require database connection setup
        $this->line("  Skipping (database connection required)");
    }

    private function runMigrations(): void
    {
        $migrateCommand = new MigrateCommand();
        $argv = ['juice', 'db:migrate', '--fresh'];
        $migrateCommand->handle($argv);
    }

    private function runSeeders(): void
    {
        $seedPath = $this->getProjectRoot() . '/seeds';
        if (!is_dir($seedPath)) {
            $this->warning("No seeders directory found");
            return;
        }

        $this->info("Running seeders...");
        // Seeders would be implemented here
        $this->line("  Seeders not implemented yet");
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . '/' . $file;
            is_dir($filePath) ? $this->removeDirectory($filePath) : unlink($filePath);
        }
        rmdir($path);
    }
}
