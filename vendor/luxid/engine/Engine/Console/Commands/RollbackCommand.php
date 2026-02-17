<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;
use Luxid\Foundation\Application;

class RollbackCommand extends Command
{
    protected string $description = 'Rollback the last migration';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $steps = $this->options['step'] ?? 1;

        $this->line("↩️  Rolling back {$steps} migration(s)...");

        // Setup application
        $this->setupApplication();

        // Ensure migrations table exists
        $this->ensureMigrationsTable();

        // Get last applied migrations
        $appliedMigrations = $this->getLastAppliedMigrations($steps);

        if (empty($appliedMigrations)) {
            $this->line("✅ No migrations to rollback");
            return 0;
        }

        $migrationsPath = $this->getMigrationsPath();
        $db = Application::$app->db;

        $rolledBack = 0;

        foreach ($appliedMigrations as $migration) {
            $migrationId = $migration['migration_id'];
            $fileName = $migrationId . '.php';
            $filePath = $migrationsPath . '/' . $fileName;

            if (!file_exists($filePath)) {
                $this->warning("Migration file not found: {$fileName}");
                continue;
            }

            $this->line("  \033[33mRolling back:\033[0m {$migrationId}");

            try {
                require_once $filePath;

                if (!class_exists($migrationId)) {
                    $this->warning("    Class {$migrationId} not found");
                    continue;
                }

                $instance = new $migrationId();
                if (method_exists($instance, 'down')) {
                    // Start transaction
                    $db->pdo->beginTransaction();

                    try {
                        $instance->down();

                        // Remove migration record
                        $stmt = $db->pdo->prepare("DELETE FROM migrations WHERE migration_id = ?");
                        $stmt->execute([$migrationId]);

                        $db->pdo->commit();
                        $this->line("    \033[32m✓ Rolled back\033[0m");
                        $rolledBack++;
                    } catch (\Exception $e) {
                        $db->pdo->rollBack();
                        throw $e;
                    }
                } else {
                    $this->warning("    No down() method found");
                }
            } catch (\Exception $e) {
                $this->error("    Failed: " . $e->getMessage());
                return 1;
            }
        }

        if ($rolledBack > 0) {
            $this->success("{$rolledBack} migration(s) rolled back successfully!");
        }

        return 0;
    }

    private function setupApplication(): void
    {
        // Load environment
        $rootPath = $this->getProjectRoot();
        $envFile = $rootPath . '/.env';

        if (file_exists($envFile)) {
            $dotenv = \Dotenv\Dotenv::createImmutable($rootPath);
            $dotenv->load();
        }

        // Create config
        $config = [
            'db' => [
                'dsn' => $_ENV['DB_DSN'] ?? '',
                'user' => $_ENV['DB_USER'] ?? '',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
            ],
        ];

        // Create application instance if not already created
        if (!isset(Application::$app)) {
            new Application($rootPath, $config);
        }
    }

    private function ensureMigrationsTable(): void
    {
        $db = Application::$app->db;

        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_id VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_migration_id (migration_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->pdo->exec($sql);
    }

    private function getLastAppliedMigrations(int $limit = 1): array
    {
        $db = Application::$app->db;

        try {
            $stmt = $db->pdo->prepare("SELECT migration_id FROM migrations ORDER BY applied_at DESC LIMIT ?");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }
}
