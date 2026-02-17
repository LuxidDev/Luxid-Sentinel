<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;
use Luxid\Foundation\Application;

class DbCreateCommand extends Command
{
    protected string $description = 'Create a new database';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->line("🗄️  Creating database...");

        // Load environment
        $rootPath = $this->getProjectRoot();
        $envFile = $rootPath . '/.env';

        if (!file_exists($envFile)) {
            $this->error(".env file not found");
            $this->line("Create .env file from .env.example");
            return 1;
        }

        $dotenv = \Dotenv\Dotenv::createImmutable($rootPath);
        $dotenv->load();

        $dsn = $_ENV['DB_DSN'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        if (empty($dsn)) {
            $this->error("DB_DSN not set in .env");
            return 1;
        }

        // Extract database name from DSN
        $dbName = $this->extractDatabaseName($dsn);

        if (!$dbName) {
            $this->error("Could not extract database name from DSN");
            return 1;
        }

        // Create connection to server (without database)
        $serverDsn = $this->removeDatabaseFromDsn($dsn);

        try {
            $pdo = new \PDO($serverDsn, $user, $password);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Create database
            $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            $pdo->exec($sql);

            $this->success("Database '{$dbName}' created successfully!");

            // Test connection to the new database
            $testPdo = new \PDO($dsn, $user, $password);
            $this->info("Connection test successful!");

        } catch (\PDOException $e) {
            $this->error("Failed to create database: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function extractDatabaseName(string $dsn): ?string
    {
        if (preg_match('/dbname=([^;]+)/', $dsn, $matches)) {
            return $matches[1];
        }

        if (preg_match('/:([^:]+)$/', $dsn, $matches)) {
            $parts = explode(';', $matches[1]);
            foreach ($parts as $part) {
                if (strpos($part, '=') !== false) {
                    [$key, $value] = explode('=', $part, 2);
                    if (trim($key) === 'dbname') {
                        return trim($value);
                    }
                }
            }
        }

        return null;
    }

    private function removeDatabaseFromDsn(string $dsn): string
    {
        // Remove dbname parameter
        return preg_replace('/;?dbname=[^;]+/', '', $dsn);
    }
}
