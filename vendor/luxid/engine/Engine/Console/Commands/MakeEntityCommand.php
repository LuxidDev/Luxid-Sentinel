<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class MakeEntityCommand extends Command
{
    protected string $description = 'Create a new Entity class';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        if (empty($this->args)) {
            $this->error("Entity name is required");
            $this->line("Usage: php juice make:entity <name>");
            return 1;
        }

        $entityName = $this->args[0];
        $this->createEntity($entityName);

        return 0;
    }

    private function createEntity(string $entityName): void
    {
        $this->line("⚡ Creating Entity...");

        $filePath = $this->getAppPath() . '/Entities/' . $entityName . '.php';

        $content = <<<PHP
<?php
namespace App\Entities;

use Luxid\Database\DbEntity;

class {$entityName} extends DbEntity
{
    public int \$id = 0;
    public string \$created_at = '';
    public string \$updated_at = '';

    public static function tableName(): string
    {
        return '{$this->camelToSnake($entityName)}s';
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
            // Add validation rules here
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

        if ($this->createFile($filePath, $content)) {
            $relativePath = str_replace($this->getProjectRoot() . '/', '', $filePath);
            $this->success("Entity created successfully!");
            $this->line("📁 Location: \033[1;34m{$relativePath}\033[0m");

            // Show usage example
            $this->line("");
            $this->line("\033[1;33m💡 Next steps:\033[0m");
            $this->line("1. Add properties to the entity class");
            $this->line("2. Create migration: \033[1;32mphp juice make:migration create_{$this->camelToSnake($entityName)}s_table\033[0m");
            $this->line("3. Run migrations: \033[1;32mphp juice db:migrate\033[0m");
        } else {
            $this->error("Failed to create Entity");
        }
    }

    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
