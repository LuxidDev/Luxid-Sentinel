<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class MakeMiddlewareCommand extends Command
{
    protected string $description = 'Create a new middleware class';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        if (empty($this->args)) {
            $this->error("Middleware name is required");
            $this->line("Usage: php juice make:middleware <name>");
            return 1;
        }

        $middlewareName = $this->args[0];
        $this->createMiddleware($middlewareName);

        return 0;
    }

    private function createMiddleware(string $name): void
    {
        $this->line("⚡ Creating middleware...");

        $filePath = $this->getAppPath() . '/Middleware/' . $name . '.php';

        $content = <<<PHP
<?php
namespace App\Middleware;

class {$name}
{
    /**
     * Handle the request
     */
    public function handle(\$request, \$next)
    {
        // Process the request before passing to next middleware/action

        \$response = \$next(\$request);

        // Process the response before returning

        return \$response;
    }
}
PHP;

        if ($this->createFile($filePath, $content)) {
            $relativePath = str_replace($this->getProjectRoot() . '/', '', $filePath);
            $this->success("Middleware created successfully!");
            $this->line("📁 Location: \033[1;34m{$relativePath}\033[0m");

            // Show usage example
            $this->line("");
            $this->line("\033[1;33m💡 Usage example:\033[0m");
            $this->line("In your routes file:");
            $this->line("  \$router->middleware([\\App\\Middleware\\{$name}::class]);");
        } else {
            $this->error("Failed to create middleware");
        }
    }
}
