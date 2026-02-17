<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class MakeMigrationCommand extends Command
{
    protected string $description = 'Create a new migration file';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        if (empty($this->args)) {
            $this->error("Migration name is required");
            $this->line("Usage: php juice make:migration <name>");
            $this->line("Example: php juice make:migration create_users_table");
            $this->line("Example: php juice make:migration add_email_to_users_table");
            return 1;
        }

        $migrationName = $this->args[0];
        $this->createMigration($migrationName);

        return 0;
    }

    private function createMigration(string $migrationName): void
    {
        $this->line("⚡ Creating migration...");

        // Get next migration number
        $nextNumber = $this->getNextMigrationNumber();
        $className = 'm' . $nextNumber . '_' . $this->sanitizeMigrationName($migrationName);
        $fileName = $className . '.php';
        $filePath = $this->getMigrationsPath() . '/' . $fileName;

        // Determine migration type based on name
        $migrationTemplate = $this->getMigrationTemplate($migrationName, $className);

        if ($this->createFile($filePath, $migrationTemplate)) {
            $relativePath = str_replace($this->getProjectRoot() . '/', '', $filePath);
            $this->success("Migration created successfully!");
            $this->line("📁 Location: \033[1;34m{$relativePath}\033[0m");

            // Show usage
            $this->line("");
            $this->line("\033[1;33m💡 Next steps:\033[0m");
            $this->line("1. Edit the migration file to customize the SQL");
            $this->line("2. Run migration: \033[1;32mphp juice db:migrate\033[0m");
            $this->line("3. Rollback if needed: \033[1;32mphp juice db:rollback\033[0m");
        } else {
            $this->error("Failed to create migration");
        }
    }

    private function getMigrationTemplate(string $migrationName, string $className): string
    {
        // Check migration type based on naming convention
        if (strpos($migrationName, 'create_') === 0 && strpos($migrationName, '_table') !== false) {
            // Create table migration
            $tableName = str_replace(['create_', '_table'], '', $migrationName);
            return $this->createTableTemplate($className, $tableName);
        } elseif (strpos($migrationName, 'add_') === 0 && strpos($migrationName, '_to_') !== false) {
            // Add column migration
            preg_match('/add_(.+)_to_(.+)_table/', $migrationName, $matches);
            if (count($matches) === 3) {
                $column = $matches[1];
                $tableName = $matches[2];
                return $this->addColumnTemplate($className, $tableName, $column);
            }
        } elseif (strpos($migrationName, 'drop_') === 0 && strpos($migrationName, '_from_') !== false) {
            // Drop column migration
            preg_match('/drop_(.+)_from_(.+)_table/', $migrationName, $matches);
            if (count($matches) === 3) {
                $column = $matches[1];
                $tableName = $matches[2];
                return $this->dropColumnTemplate($className, $tableName, $column);
            }
        }

        // Default generic migration
        return <<<PHP
<?php
use Luxid\Database\Database;

class {$className}
{
    /**
     * Run the migration
     */
    public function apply():void
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "";

        try {
            \$db->pdo->exec(\$sql);
            echo "Migration {$className} applied successfully\\n";
        } catch (\\Exception \$e) {
            throw new \\Exception("Migration failed: " . \$e->getMessage());
        }
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "";

        try {
            \$db->pdo->exec(\$sql);
            echo "Migration {$className} reverted successfully\\n";
        } catch (\\Exception \$e) {
            throw new \\Exception("Rollback failed: " . \$e->getMessage());
        }
    }
}
PHP;
    }

    private function createTableTemplate(string $className, string $tableName): string
    {
        return <<<PHP
<?php
use Luxid\Database\Database;

class {$className}
{
    /**
     * Run the migration
     */
    public function apply(): void
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            \$db->pdo->exec(\$sql);
            echo "Table '{$tableName}' created successfully\\n";
        } catch (\\Exception \$e) {
            throw new \\Exception("Migration failed: " . \$e->getMessage());
        }
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "DROP TABLE IF EXISTS `{$tableName}`";

        try {
            \$db->pdo->exec(\$sql);
            echo "Table '{$tableName}' dropped successfully\\n";
        } catch (\\Exception \$e) {
            throw new \\Exception("Rollback failed: " . \$e->getMessage());
        }
    }
}
PHP;
    }

    private function addColumnTemplate(string $className, string $tableName, string $columnName): string
    {
        $columnType = $this->guessColumnType($columnName);

        return <<<PHP
<?php
use Luxid\Database\Database;

class {$className}
{
    /**
     * Run the migration
     */
    public function apply(): void
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$columnType}";

        try {
            \$db->pdo->exec(\$sql);
            echo "Column '{$columnName}' added to '{$tableName}' successfully\\n";
        } catch (\\Exception \$e) {
            throw new \\Exception("Migration failed: " . \$e->getMessage());
        }
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`";

        try {
            \$db->pdo->exec(\$sql);
            echo "Column '{$columnName}' removed from '{$tableName}' successfully\\n";
        } catch (\\Exception \$e) {
            throw new \\Exception("Rollback failed: " . \$e->getMessage());
        }
    }
}
PHP;
    }

    private function dropColumnTemplate(string $className, string $tableName, string $columnName): string
    {
        return <<<PHP
<?php
use Luxid\Database\Database;

class {$className}
{
    /**
     * Run the migration
     */
    public function apply(): void
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`";

        try {
            \$db->pdo->exec(\$sql);
            echo "Column '{$columnName}' removed from '{$tableName}' successfully\\n";
        } catch (\\Exception \$e) {
            throw new \\Exception("Migration failed: " . \$e->getMessage());
        }
    }

    /**
     * Reverse the migration
     */
    public function down()
    {
        // Here you would need to know the original column definition
        // This is just a placeholder - you should update this with the actual column definition
        \$columnType = "VARCHAR(255)";

        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$columnType}";

        try {
            \$db->pdo->exec(\$sql);
            echo "Column '{$columnName}' added back to '{$tableName}' successfully\\n";
        } catch (\\Exception \$e) {
            throw new \\Exception("Rollback failed: " . \$e->getMessage());
        }
    }
}
PHP;
    }

    private function guessColumnType(string $columnName): string
    {
        // Simple type guessing based on column name
        if (strpos($columnName, 'email') !== false) {
            return "VARCHAR(255)";
        } elseif (strpos($columnName, 'password') !== false) {
            return "VARCHAR(255)";
        } elseif (strpos($columnName, 'name') !== false) {
            return "VARCHAR(255)";
        } elseif (strpos($columnName, 'description') !== false) {
            return "TEXT";
        } elseif (strpos($columnName, 'content') !== false) {
            return "TEXT";
        } elseif (strpos($columnName, 'amount') !== false || strpos($columnName, 'price') !== false) {
            return "DECIMAL(10,2)";
        } elseif (strpos($columnName, 'quantity') !== false) {
            return "INT";
        } elseif (strpos($columnName, 'is_') === 0 || strpos($columnName, 'has_') === 0) {
            return "TINYINT(1) DEFAULT 0";
        } elseif (strpos($columnName, 'date') !== false) {
            return "DATE";
        } elseif (strpos($columnName, 'time') !== false) {
            return "TIME";
        } elseif (strpos($columnName, 'at') !== false) {
            return "TIMESTAMP NULL DEFAULT NULL";
        } else {
            return "VARCHAR(255)";
        }
    }

    private function getNextMigrationNumber(): string
    {
        $migrationsPath = $this->getMigrationsPath();
        $this->ensureDirectory($migrationsPath);

        $files = scandir($migrationsPath);
        $maxNumber = 0;

        foreach ($files as $file) {
            if (preg_match('/^m(\d{5})_/', $file, $matches)) {
                $number = (int) $matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        return str_pad($maxNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    private function sanitizeMigrationName(string $name): string
    {
        // Replace non-alphanumeric with underscores
        $name = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
        // Remove multiple underscores
        $name = preg_replace('/_+/', '_', $name);
        // Remove leading/trailing underscores
        $name = trim($name, '_');

        return $name;
    }
}
