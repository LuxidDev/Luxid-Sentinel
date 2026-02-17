<?php
// Engine/Console/Commands/MigrateCommand.php
namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class MigrateCommand extends Command
{
    protected string $description = 'Run database migrations';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $fresh = $this->options['fresh'] ?? false;

        if ($fresh) {
            $this->line("🧹 Fresh migration: dropping all tables and re-running migrations");

            if (!$this->confirm("This will drop all tables! Are you sure?", false)) {
                $this->line("Operation cancelled");
                return 0;
            }

            // For fresh, we need to manually drop tables
            $this->setupMinimalApplication();
            $db = $this->getDatabaseConnection();

            try {
                $stmt = $db->pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if (!empty($tables)) {
                    $this->line("Dropping tables...");
                    $db->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

                    foreach ($tables as $table) {
                        $this->line("  Dropping: {$table}");
                        $db->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                    }

                    $db->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                }
            } catch (\Exception $e) {
                $this->error("Failed to drop tables: " . $e->getMessage());
                return 1;
            }
        }

        return $this->runMigrations();
    }

    private function runMigrations(): int
    {
        $this->line("🔄 Running migrations...");

        try {
            // Setup minimal application for migrations
            $this->setupMinimalApplication();
            $db = $this->getDatabaseConnection();

            // Use Luxid's built-in migration system
            $db->applyMigrations();

            $this->success("Migrations applied successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
            return 1;
        }
    }

    private function setupMinimalApplication(): void
    {
        // Load environment
        $rootPath = $this->getProjectRoot();
        $envFile = $rootPath . '/.env';

        if (file_exists($envFile)) {
            $dotenv = \Dotenv\Dotenv::createImmutable($rootPath);
            $dotenv->load();
        }

        // Create minimal config
        $config = [
            'db' => [
                'dsn' => $_ENV['DB_DSN'] ?? 'mysql:host=127.0.0.1;port=3306;dbname=luxid_todo',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ],
            'userClass' => '', // Empty for CLI
        ];

        // Create Application instance but prevent session start
        if (!class_exists('Luxid\Foundation\Application')) {
            require_once $this->getProjectRoot() . '/vendor/autoload.php';
        }

        // Temporarily override session_start to prevent errors
        if (!function_exists('session_start')) {
            function session_start($options = []) {
                return true; // No-op for CLI
            }
        }

        // Create application
        new \Luxid\Foundation\Application($rootPath, $config);
    }

    private function getDatabaseConnection(): \Luxid\Database\Database
    {
        return \Luxid\Foundation\Application::$app->db;
    }
}
