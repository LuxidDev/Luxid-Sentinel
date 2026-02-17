<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class EnvCheckCommand extends Command
{
    protected string $description = 'Validate environment configuration';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->line("🔧 Environment Configuration Check");
        $this->line(str_repeat("─", 60));

        $errors = [];
        $warnings = [];
        $success = [];

        // Check .env file
        $envFile = $this->getProjectRoot() . '/.env';
        if (file_exists($envFile)) {
            $success[] = ['.env file', '✓ Found', 'Found'];
            $env = parse_ini_file($envFile);
        } else {
            $errors[] = ['.env file', '✗ Missing', 'Create from .env.example'];
        }

        // Required environment variables
        $requiredVars = [
            'APP_ENV' => ['development', 'production', 'testing'],
            'APP_DEBUG' => ['true', 'false'],
            'DB_DSN' => 'string',
            'DB_USER' => 'string',
            'DB_PASSWORD' => 'string',
        ];

        if (isset($env)) {
            foreach ($requiredVars as $var => $expected) {
                if (!isset($env[$var])) {
                    $errors[] = [$var, '✗ Missing', 'Required'];
                    continue;
                }

                $value = $env[$var];

                if (is_array($expected)) {
                    if (!in_array($value, $expected)) {
                        $warnings[] = [$var, '⚠ Invalid', "Expected: " . implode(', ', $expected)];
                    } else {
                        $success[] = [$var, '✓ Valid', $value];
                    }
                } elseif ($expected === 'string') {
                    if (empty($value)) {
                        $warnings[] = [$var, '⚠ Empty', 'Should not be empty'];
                    } else {
                        $success[] = [$var, '✓ Set', substr($value, 0, 20) . (strlen($value) > 20 ? '...' : '')];
                    }
                }
            }
        }

        // Check optional variables
        $optionalVars = [
            'APP_URL' => 'string',
            'APP_TIMEZONE' => 'string',
            'SESSION_LIFETIME' => 'integer',
        ];

        if (isset($env)) {
            foreach ($optionalVars as $var => $type) {
                if (isset($env[$var])) {
                    $value = $env[$var];
                    if ($type === 'integer' && !is_numeric($value)) {
                        $warnings[] = [$var, '⚠ Invalid', "Should be a number"];
                    } else {
                        $success[] = [$var, '✓ Optional', $value];
                    }
                }
            }
        }

        // Display results
        if (!empty($success)) {
            $this->line("\033[1;32m✓ Passed checks:\033[0m");
            $this->table(['Variable', 'Status', 'Value'], $success);
        }

        if (!empty($warnings)) {
            $this->line("\n\033[1;33m⚠ Warnings:\033[0m");
            $this->table(['Variable', 'Status', 'Message'], $warnings);
        }

        if (!empty($errors)) {
            $this->line("\n\033[1;31m✗ Errors:\033[0m");
            $this->table(['Variable', 'Status', 'Message'], $errors);
        }

        $this->line(str_repeat("─", 60));

        // Summary
        $total = count($success) + count($warnings) + count($errors);
        $passed = count($success);

        if (empty($errors) && empty($warnings)) {
            $this->success("All checks passed! ({$passed}/{$total})");
            return 0;
        } elseif (empty($errors)) {
            $this->warning("Checks completed with warnings ({$passed}/{$total})");
            return 0;
        } else {
            $this->error("Checks failed ({$passed}/{$total})");
            return 1;
        }
    }
}
