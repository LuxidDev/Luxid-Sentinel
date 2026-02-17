<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class MakeApiCommand extends Command
{
    protected string $description = 'Generate a complete API CRUD for a resource';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        if (empty($this->args)) {
            $this->error("Resource name is required");
            $this->line("Usage: php juice make:api <resource>");
            $this->line("Example: php juice make:api Product");
            return 1;
        }

        $resource = $this->args[0];
        $this->generateApi($resource);

        return 0;
    }

    private function generateApi(string $resource): void
    {
        $this->line("🚀 Generating API for: {$resource}");

        // Create entity
        $this->createEntity($resource);

        // Create action
        $this->createAction($resource);

        // Create migration
        $this->createMigration($resource);

        // Add routes
        $this->addRoutes($resource);

        $this->line("");
        $this->success("API for '{$resource}' generated successfully!");
        $this->line("");
        $this->line("\033[1;33m🎯 Next steps:\033[0m");
        $this->line("1. Edit the entity to add properties: \033[1;34mapp/Entities/{$resource}.php\033[0m");
        $this->line("2. Edit the migration: \033[1;34mmigrations/mXXXXX_create_{$this->camelToSnake($resource)}s_table.php\033[0m");
        $this->line("3. Run migration: \033[1;32mphp juice db:migrate\033[0m");
        $this->line("4. Start server: \033[1;32mphp juice start\033[0m");
        $this->line("5. Test API at: \033[1;34mhttp://localhost:8000/api/{$this->camelToSnake($resource)}s\033[0m");
    }

    private function createEntity(string $resource): void
    {
        $content = <<<PHP
<?php
namespace App\Entities;

use Luxid\Database\DbEntity;

class {$resource} extends DbEntity
{
    public int \$id = 0;
    public string \$created_at = '';
    public string \$updated_at = '';

    public static function tableName(): string
    {
        return '{$this->camelToSnake($resource)}s';
    }

    public static function primaryKey(): string
    {
        return 'id';
    }

    public function attributes(): array
    {
        return ['created_at', 'updated_at'];
    }

    public function rules(): array
    {
        return [
            // Add validation rules for your properties
        ];
    }

    public function save(): bool
    {
        if (\$this->id === 0) {
            \$this->created_at = date('Y-m-d H:i:s');
        }
        \$this->updated_at = date('Y-m-d H:i:s');
        return parent::save();
    }
}
PHP;

        $filePath = $this->getAppPath() . '/Entities/' . $resource . '.php';
        if ($this->createFile($filePath, $content)) {
            $this->info("Entity created: App/Entities/{$resource}.php");
        }
    }

    private function createAction(string $resource): void
    {
        $content = <<<PHP
<?php
namespace App\Actions;

class {$resource}Action extends Action
{
    /**
     * Get all {$resource}s
     */
    public function index()
    {
        \$items = \\App\\Entities\\{$resource}::findAll();
        return \$this->success(['{$this->camelToSnake($resource)}s' => \$items]);
    }

    /**
     * Get single {$resource}
     */
    public function show(\$id)
    {
        \$item = \\App\\Entities\\{$resource}::findOne(['id' => \$id]);

        if (!\$item) {
            return \$this->error('{$resource} not found', 404);
        }

        return \$this->success(['{$this->camelToSnake($resource)}' => \$item]);
    }

    /**
     * Create new {$resource}
     */
    public function store()
    {
        \$data = \$this->getRequestData();

        \$item = new \\App\\Entities\\{$resource}();
        \$item->loadData(\$data);

        if (\$item->validate() && \$item->save()) {
            return \$this->success(['{$this->camelToSnake($resource)}' => \$item], 201);
        }

        return \$this->error('Validation failed', 400, \$item->errors);
    }

    /**
     * Update {$resource}
     */
    public function update(\$id)
    {
        \$item = \\App\\Entities\\{$resource}::findOne(['id' => \$id]);

        if (!\$item) {
            return \$this->error('{$resource} not found', 404);
        }

        \$data = \$this->getRequestData();
        \$item->loadData(\$data);

        if (\$item->validate() && \$item->save()) {
            return \$this->success(['{$this->camelToSnake($resource)}' => \$item]);
        }

        return \$this->error('Validation failed', 400, \$item->errors);
    }

    /**
     * Delete {$resource}
     */
    public function destroy(\$id)
    {
        \$item = \\App\\Entities\\{$resource}::findOne(['id' => \$id]);

        if (!\$item) {
            return \$this->error('{$resource} not found', 404);
        }

        if (\$item->delete()) {
            return \$this->success(['message' => '{$resource} deleted successfully']);
        }

        return \$this->error('Failed to delete {$resource}', 500);
    }

    /**
     * Get request data (JSON or form)
     */
    private function getRequestData(): array
    {
        \$contentType = \$_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos(\$contentType, 'application/json') !== false) {
            \$json = file_get_contents('php://input');
            return json_decode(\$json, true) ?? [];
        }

        return \$_POST;
    }
}
PHP;

        $filePath = $this->getAppPath() . '/Actions/' . $resource . 'Action.php';
        if ($this->createFile($filePath, $content)) {
            $this->info("Action created: App/Actions/{$resource}Action.php");
        }
    }

    private function createMigration(string $resource): void
    {
        $nextNumber = $this->getNextMigrationNumber();
        $tableName = $this->camelToSnake($resource) . 's';
        $className = 'm' . $nextNumber . '_create_' . $tableName . '_table';

        $content = <<<PHP
<?php
use Luxid\Database\Database;

class {$className}
{
    public function apply()
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        \$db->pdo->exec(\$sql);
    }

    public function down()
    {
        \$db = \\Luxid\\Foundation\\Application::\$app->db;

        \$sql = "DROP TABLE IF EXISTS `{$tableName}`";

        \$db->pdo->exec(\$sql);
    }
}
PHP;

        $filePath = $this->getMigrationsPath() . '/' . $className . '.php';
        if ($this->createFile($filePath, $content)) {
            $this->info("Migration created: migrations/{$className}.php");
        }
    }

    private function addRoutes(string $resource): void
    {
        $routesFile = $this->getRoutesPath() . '/api.php';
        $routeName = $this->camelToSnake($resource) . 's';

        // Read existing content
        $content = file_exists($routesFile) ? file_get_contents($routesFile) : '<?php' . PHP_EOL . PHP_EOL;

        // Add resource routes
        $routes = PHP_EOL . PHP_EOL . '// ' . $resource . ' Routes' . PHP_EOL;
        $routes .= "\$router->get('/{$routeName}', [\\App\\Actions\\{$resource}Action::class, 'index']);" . PHP_EOL;
        $routes .= "\$router->get('/{$routeName}/{id}', [\\App\\Actions\\{$resource}Action::class, 'show']);" . PHP_EOL;
        $routes .= "\$router->post('/{$routeName}', [\\App\\Actions\\{$resource}Action::class, 'store']);" . PHP_EOL;
        $routes .= "\$router->put('/{$routeName}/{id}', [\\App\\Actions\\{$resource}Action::class, 'update']);" . PHP_EOL;
        $routes .= "\$router->delete('/{$routeName}/{id}', [\\App\\Actions\\{$resource}Action::class, 'destroy']);" . PHP_EOL;

        // Append routes
        $content .= $routes;

        if (file_put_contents($routesFile, $content)) {
            $this->info("Routes added: /api/{$routeName}");
        }
    }

    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
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
}
