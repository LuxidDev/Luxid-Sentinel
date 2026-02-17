<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class MakeActionCommand extends Command
{
    protected string $description = 'Create a new Action class';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        if (empty($this->args)) {
            $this->error("Action name is required");
            $this->line("Usage: php juice make:action <name>");
            return 1;
        }

        $actionName = $this->args[0];
        $this->createAction($actionName);

        return 0;
    }

    private function createAction(string $actionName): void
    {
        $this->line("⚡ Creating Action...");

        // Handle nested paths (Users/ListAction)
        if (strpos($actionName, '/') !== false) {
            $parts = explode('/', $actionName);
            $className = array_pop($parts);
            $namespace = 'App\\Actions\\' . implode('\\', $parts);
            $directory = $this->getAppPath() . '/Actions/' . implode('/', $parts);
        } else {
            $className = $actionName;
            $namespace = 'App\\Actions';
            $directory = $this->getAppPath() . '/Actions';
        }

        $filePath = $directory . '/' . $className . '.php';

        $content = <<<PHP
<?php
namespace {$namespace};

use Luxid\Foundation\Action;

class {$className} extends Action
{
    /**
     * Action method
     */
    public function index()
    {
        // Your action logic here
        return \$this->success(['message' => 'Action executed successfully']);
    }
}
PHP;

        if ($this->createFile($filePath, $content)) {
            $relativePath = str_replace($this->getProjectRoot() . '/', '', $filePath);
            $this->success("Action created successfully!");
            $this->line("📁 Location: \033[1;34m{$relativePath}\033[0m");

            // Show usage example
            $this->line("");
            $this->line("\033[1;33m💡 Usage example:\033[0m");
            $this->line("Add to your routes file:");
            $this->line("  \$router->get('/example', [\\{$namespace}\\{$className}::class, 'index']);");
        } else {
            $this->error("Failed to create Action");
        }
    }
}
