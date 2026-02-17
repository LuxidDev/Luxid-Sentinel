<?php

namespace Luxid\Console;

abstract class Command
{
    protected array $args = [];
    protected array $options = [];
    protected string $description = '';
    protected string $projectRoot;

    abstract public function handle(array $argv): int;

    public function __construct()
    {
        $this->projectRoot = getcwd();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    protected function parseArguments(array $argv): void
    {
        $this->args = [];
        $this->options = [];

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $option = substr($arg, 2);
                if (strpos($option, '=') !== false) {
                    [$key, $value] = explode('=', $option, 2);
                    $this->options[$key] = $value;
                } else {
                    $this->options[$option] = true;
                }
            } elseif ($arg !== $argv[0] && $arg !== $argv[1]) {
                $this->args[] = $arg;
            }
        }
    }

    protected function info(string $message): void
    {
        echo "\033[32m✓ " . $message . "\033[0m" . PHP_EOL;
    }

    protected function warning(string $message): void
    {
        echo "\033[33m⚠ " . $message . "\033[0m" . PHP_EOL;
    }

    protected function error(string $message): void
    {
        echo "\033[31m❌ " . $message . "\033[0m" . PHP_EOL;
    }

    protected function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    protected function success(string $message): void
    {
        echo "\033[32m🎉 " . $message . "\033[0m" . PHP_EOL;
    }

    protected function table(array $headers, array $rows): void
    {
        // Calculate column widths
        $widths = [];
        foreach ($headers as $i => $header) {
            $widths[$i] = strlen($header);
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen($cell));
            }
        }

        // Print headers
        $headerLine = '';
        foreach ($headers as $i => $header) {
            $headerLine .= str_pad($header, $widths[$i] + 2);
        }
        $this->line("\033[1;34m" . $headerLine . "\033[0m");

        // Print separator
        $separator = '';
        foreach ($widths as $width) {
            $separator .= str_repeat('─', $width + 2);
        }
        $this->line($separator);

        // Print rows
        foreach ($rows as $row) {
            $rowLine = '';
            foreach ($row as $i => $cell) {
                $rowLine .= str_pad($cell, $widths[$i] + 2);
            }
            $this->line($rowLine);
        }
    }

    protected function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        echo "\033[33m{$question} [{$defaultText}]: \033[0m";

        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);

        if ($response === '') {
            return $default;
        }

        return in_array(strtolower($response), ['y', 'yes', '1']);
    }

    protected function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

    protected function getAppPath(): string
    {
        return $this->projectRoot . '/app';
    }

    protected function getMigrationsPath(): string
    {
        return $this->projectRoot . '/migrations';
    }

    protected function getConfigPath(): string
    {
        return $this->projectRoot . '/config';
    }

    protected function getRoutesPath(): string
    {
        return $this->projectRoot . '/routes';
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            $this->info("Created directory: {$path}");
        }
    }

    protected function createFile(string $path, string $content): bool
    {
        $this->ensureDirectory(dirname($path));

        if (file_exists($path) && !($this->options['force'] ?? false)) {
            $this->warning("File already exists: {$path}");
            if (!$this->confirm("Overwrite?")) {
                return false;
            }
        }

        return file_put_contents($path, $content) !== false;
    }
}
