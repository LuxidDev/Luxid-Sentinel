<?php

declare(strict_types=1);

namespace Luxid\Sentinel\Console;

use Luxid\Console\Command;

/**
 * Install command for Sentinel authentication package.
 *
 * Run with: php juice sentinel:install
 *
 * @package Luxid\Sentinel\Console
 */
class InstallCommand extends Command
{
    protected string $description = 'Install Sentinel authentication package';

    protected string $stubsPath;
    protected string $projectRoot;

    public function __construct()
    {
        parent::__construct();

        $this->stubsPath = dirname(__DIR__, 2) . '/stubs';
        $this->projectRoot = $this->getProjectRoot();
    }

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->line('🍋 Installing Luxid Sentinel...');
        $this->line('');

        try {
            if ($this->isInstalled() && !$this->shouldForce()) {
                $this->warning('Sentinel appears to be already installed.');
                if (!$this->confirm('Do you want to reinstall? This may overwrite existing files.')) {
                    $this->line('Installation cancelled.');
                    return 0;
                }
            }

            $this->publishConfig();
            $this->updateMigration();
            $this->generateEntity(); // This will now update existing User entity
            $this->generateActions();
            $this->registerRoutes();

            $this->success('Sentinel installed successfully!');
            $this->line('');
            $this->line('📋 Next steps:');
            $this->line('   1. Review config/sentinel.php');
            $this->line('   2. Run: php juice db:migrate');
            $this->line('   3. Test your auth endpoints:');
            $this->line('      POST   /register');
            $this->line('      POST   /login');
            $this->line('      POST   /logout');
            $this->line('      GET    /me');
            $this->line('');

            return 0;
        } catch (\Throwable $e) {
            $this->error('Installation failed: ' . $e->getMessage());
            return 1;
        }
    }

    protected function isInstalled(): bool
    {
        return file_exists($this->projectRoot . '/config/sentinel.php') ||
               file_exists($this->projectRoot . '/app/Entities/User.php');
    }

    protected function shouldForce(): bool
    {
        return isset($this->options['force']) || in_array('--force', $_SERVER['argv'] ?? []);
    }

    protected function publishConfig(): void
    {
        $this->line('📄 Publishing configuration...');

        $source = $this->stubsPath . '/config/sentinel.php.stub';
        $target = $this->projectRoot . '/config/sentinel.php';

        $this->ensureDirectory(dirname($target));

        if (file_exists($target) && !$this->shouldForce()) {
            $this->warning('Config file already exists. Skipping...');
            return;
        }

        $content = str_replace(
            ['{{namespace}}', '{{userClass}}'],
            ['App\\Entities', 'App\\Entities\\User'],
            file_get_contents($source)
        );

        if (file_put_contents($target, $content)) {
            $this->info('Config published: config/sentinel.php');
        } else {
            throw new \RuntimeException('Failed to publish config file.');
        }
    }

    /**
     * Update the existing users table migration to add remember_token
     */
    protected function updateMigration(): void
    {
        $this->line('🗄️  Updating users table migration...');

        $migrationsPath = $this->projectRoot . '/migrations';

        if (!is_dir($migrationsPath)) {
            $this->warning('Migrations directory not found. Creating new migration instead...');
            $this->createMigration();
            return;
        }

        // Find the users table migration (m00001_create_users_table.php)
        $files = scandir($migrationsPath);
        $usersMigration = null;

        foreach ($files as $file) {
            // Look for the pattern: m00001_create_users_table.php
            if (preg_match('/^m\d+_create_users_table\.php$/', $file)) {
                $usersMigration = $file;
                break;
            }
        }

        if (!$usersMigration) {
            $this->warning('Users table migration not found. Creating new migration...');
            $this->createMigration();
            return;
        }

        $migrationFile = $migrationsPath . '/' . $usersMigration;
        $content = file_get_contents($migrationFile);

        // Check if remember_token already exists
        if (strpos($content, 'remember_token') !== false) {
            $this->info('✓ remember_token already exists in migration. Skipping...');
            return;
        }

        // Add remember_token column to the existing migration
        // Find the position before the closing parenthesis and ENGINE clause
        $pattern = '/(\s*\)\s*ENGINE\s*=\s*InnoDB.*;)/';

        // The remember_token column definition
        $rememberTokenColumn = ",\n            remember_token VARCHAR(100) NULL,\n            INDEX idx_remember_token (remember_token)";

        $newContent = preg_replace($pattern, $rememberTokenColumn . '$1', $content);

        if ($newContent && $newContent !== $content) {
            // Backup the original file
            $backupFile = $migrationFile . '.backup';
            copy($migrationFile, $backupFile);

            if (file_put_contents($migrationFile, $newContent)) {
                $this->info("✅ Updated migration: {$usersMigration} (added remember_token column)");
                $this->line("   Backup created: {$usersMigration}.backup");
            } else {
                // Restore from backup if write fails
                if (file_exists($backupFile)) {
                    copy($backupFile, $migrationFile);
                    unlink($backupFile);
                }
                throw new \RuntimeException('Failed to update migration file.');
            }
        }
    }

    /**
     * Create a new migration for users table (fallback if no existing migration found)
     */
    protected function createMigration(): void
    {
        $this->line('📝 Creating new users table migration...');

        $source = $this->stubsPath . '/migrations/create_users_table.php.stub';

        $nextNumber = $this->getNextMigrationNumber();
        $filename = sprintf('m%05d_create_users_table.php', $nextNumber);
        $target = $this->projectRoot . '/migrations/' . $filename;

        $this->ensureDirectory(dirname($target));

        if (file_exists($target) && !$this->shouldForce()) {
            $this->warning('Migration file already exists. Skipping...');
            return;
        }

        $content = file_get_contents($source);

        // Replace the class name placeholder
        $className = sprintf('m%05d_create_users_table', $nextNumber);
        $content = str_replace('{{class}}', $className, $content);

        if (file_put_contents($target, $content)) {
            $this->info("✅ Migration created: migrations/{$filename}");
        } else {
            throw new \RuntimeException('Failed to create migration file.');
        }
    }

    /**
     * Get the next migration number based on existing migrations.
     *
     * @return int
     */
    protected function getNextMigrationNumber(): int
    {
        $migrationsPath = $this->projectRoot . '/migrations';

        if (!is_dir($migrationsPath)) {
            return 1;
        }

        $files = scandir($migrationsPath);
        $maxNumber = 0;

        foreach ($files as $file) {
            if (preg_match('/^m(\d+)_/', $file, $matches)) {
                $number = (int) $matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        return $maxNumber + 1;
    }

    /**
     * Generate or update User entity with Authenticatable interface.
     */
    protected function generateEntity(): void
    {
        $this->line('👤 Setting up User entity...');

        $targetDir = $this->projectRoot . '/app/Entities';
        $this->ensureDirectory($targetDir);

        $target = $targetDir . '/User.php';

        if (!file_exists($target)) {
            // File doesn't exist - create from stub
            $this->createUserEntityFromStub();
            return;
        }

        // File exists - update it with Authenticatable interface and remember_token
        $this->updateUserEntity();
    }

    /**
     * Create a new User entity from stub.
     */
    protected function createUserEntityFromStub(): void
    {
        $source = $this->stubsPath . '/entities/User.php.stub';
        $target = $this->projectRoot . '/app/Entities/User.php';

        $content = str_replace(
            ['{{namespace}}', '{{tableName}}', '{{primaryKey}}'],
            ['App\\Entities', 'users', 'id'],
            file_get_contents($source)
        );

        if (file_put_contents($target, $content)) {
            $this->info('✅ User entity created: app/Entities/User.php');
        } else {
            throw new \RuntimeException('Failed to create User entity.');
        }
    }

    /**
     * Update existing User entity with Authenticatable interface and remember_token.
     */
    protected function updateUserEntity(): void
    {
        $target = $this->projectRoot . '/app/Entities/User.php';
        $content = file_get_contents($target);
        $originalContent = $content;
        $changes = false;

        // 1. Add use statement for Authenticatable if not present
        if (strpos($content, 'use Luxid\\Sentinel\\Contracts\\Authenticatable;') === false) {
            $content = preg_replace(
                '/(namespace App\\\\Entities;\n)/',
                "$1\nuse Luxid\\Sentinel\\Contracts\\Authenticatable;\n",
                $content
            );
            $this->info('✓ Added Authenticatable use statement');
            $changes = true;
        }

        // 2. Add implements Authenticatable
        if (strpos($content, 'implements Authenticatable') === false) {
            $content = str_replace(
                'extends UserEntity',
                'extends UserEntity implements Authenticatable',
                $content
            );
            $this->info('✓ Added Authenticatable interface');
            $changes = true;
        }

        // 3. Add remember_token property
        if (strpos($content, 'public ?string $remember_token = null;') === false) {
            // Add after lastname property
            $pattern = '/(public string \$lastname = \'\';)/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace(
                    $pattern,
                    "$1\n    public ?string \$remember_token = null;",
                    $content
                );
                $this->info('✓ Added remember_token property');
                $changes = true;
            }
        }

        // 4. Add updated_at property if not present
        if (strpos($content, 'public string $updated_at =') === false) {
            $pattern = '/(public string \$created_at = \'\';)/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace(
                    $pattern,
                    "$1\n    public string \$updated_at = '';",
                    $content
                );
                $this->info('✓ Added updated_at property');
                $changes = true;
            }
        }

        // 5. Update attributes() array to include remember_token and updated_at
        if (preg_match('/public function attributes\(\): array\s*\{\s*return \[(.*?)\];\s*\}/s', $content, $matches)) {
            $attributes = explode(',', $matches[1]);
            $attributes = array_map('trim', $attributes);
            $originalAttributes = $attributes;

            if (!in_array("'remember_token'", $attributes)) {
                $attributes[] = "'remember_token'";
            }
            if (!in_array("'updated_at'", $attributes)) {
                $attributes[] = "'updated_at'";
            }

            if ($attributes !== $originalAttributes) {
                $newAttributes = "return [" . implode(', ', $attributes) . "];";
                $content = str_replace($matches[0], "    public function attributes(): array\n    {\n        " . $newAttributes . "\n    }", $content);
                $this->info('✓ Updated attributes() array');
                $changes = true;
            }
        }

        // 6. Add Authenticatable methods if missing
        $methods = [
            'getAuthIdentifierName',
            'getAuthIdentifier',
            'getAuthPassword',
            'getAuthPasswordName',
            'getRememberToken',
            'setRememberToken',
            'getRememberTokenName',
        ];

        $missingMethods = [];
        foreach ($methods as $method) {
            if (strpos($content, "function $method") === false) {
                $missingMethods[] = $method;
            }
        }

        if (!empty($missingMethods)) {
            $methodsCode = $this->getAuthenticatableMethods();
            // Remove the last closing brace and append methods
            $content = rtrim($content, '}') . "\n" . $methodsCode . "\n}";
            $this->info('✓ Added Authenticatable interface methods');
            $changes = true;
        }

        // 7. Update save() method to handle timestamps
        if (preg_match('/public function save\(\): bool\s*\{.*?\}/s', $content, $saveMatch)) {
            $saveMethod = $saveMatch[0];

            if (strpos($saveMethod, '$this->updated_at') === false) {
                // Create updated save method
                $newSaveMethod = <<<'PHP'
    public function save(): bool
    {
        // Hash password before saving if it's plain text
        if (!empty($this->password) && !password_get_info($this->password)['algo']) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }

        if ($this->id === 0) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->updated_at = date('Y-m-d H:i:s');

        return parent::save();
    }
PHP;
                $content = str_replace($saveMethod, $newSaveMethod, $content);
                $this->info('✓ Updated save() method with timestamps');
                $changes = true;
            }
        }

        // 8. Add toArray() method if not present
        if (strpos($content, 'public function toArray') === false) {
            $toArrayMethod = <<<'PHP'

    /**
     * Convert user to array for API responses.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'display_name' => $this->getDisplayName(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
PHP;
            // Add before the last closing brace
            $content = rtrim($content, '}') . $toArrayMethod . "\n}";
            $this->info('✓ Added toArray() method');
            $changes = true;
        }

        // Write changes if any
        if ($changes) {
            // Create backup
            $backupFile = $target . '.backup';
            copy($target, $backupFile);

            if (file_put_contents($target, $content)) {
                $this->info("✅ User entity updated successfully");
                $this->line("   Backup created: User.php.backup");
            } else {
                // Restore from backup if write fails
                if (file_exists($backupFile)) {
                    copy($backupFile, $target);
                    unlink($backupFile);
                }
                throw new \RuntimeException('Failed to update User entity.');
            }
        } else {
            $this->info('✓ User entity already up to date');
        }
    }

    /**
     * Get the Authenticatable interface methods.
     *
     * @return string
     */
    protected function getAuthenticatableMethods(): string
    {
        return <<<'PHP'

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return static::primaryKey();
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->{static::primaryKey()};
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    /**
     * Get the column name where password is stored.
     *
     * @return string
     */
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    /**
     * Get the "remember me" token value.
     *
     * @return string|null
     */
    public function getRememberToken(): ?string
    {
        return $this->remember_token;
    }

    /**
     * Set the "remember me" token value.
     *
     * @param string|null $value
     * @return void
     */
    public function setRememberToken(?string $value): void
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
PHP;
    }

    protected function generateActions(): void
    {
        $this->line('⚡ Generating authentication actions...');

        $targetDir = $this->projectRoot . '/app/Actions/Auth';
        $this->ensureDirectory($targetDir);

        $actions = [
            'RegisterAction',
            'LoginAction',
            'LogoutAction',
            'MeAction',
        ];

        foreach ($actions as $action) {
            $source = $this->stubsPath . "/actions/Auth/{$action}.php.stub";
            $target = $targetDir . "/{$action}.php";

            if (file_exists($target) && !$this->shouldForce()) {
                $this->warning("Action {$action} already exists. Skipping...");
                continue;
            }

            $content = str_replace(
                ['{{namespace}}', '{{userClass}}'],
                ['App\\Actions\\Auth', 'App\\Entities\\User'],
                file_get_contents($source)
            );

            if (file_put_contents($target, $content)) {
                $this->info("✓ Action generated: app/Actions/Auth/{$action}.php");
            } else {
                throw new \RuntimeException("Failed to generate {$action}.");
            }
        }
    }

    protected function registerRoutes(): void
    {
        $this->line('🛣️  Registering authentication routes...');

        $routesFile = $this->projectRoot . '/routes/api.php';

        if (!file_exists($routesFile)) {
            $this->ensureDirectory(dirname($routesFile));
            file_put_contents($routesFile, "<?php\n\n// API Routes\n");
        }

        $content = file_get_contents($routesFile);

        if (strpos($content, 'Auth\\RegisterAction') !== false) {
            $this->warning('Auth routes already registered. Skipping...');
            return;
        }

        $authRoutes = <<<'PHP'

// Authentication Routes (Sentinel)
route('auth.register')
    ->post('/register')
    ->uses(\App\Actions\Auth\RegisterAction::class, 'index')
    ->public();

route('auth.login')
    ->post('/login')
    ->uses(\App\Actions\Auth\LoginAction::class, 'index')
    ->public();

route('auth.logout')
    ->post('/logout')
    ->uses(\App\Actions\Auth\LogoutAction::class, 'index')
    ->auth();

route('auth.me')
    ->get('/me')
    ->uses(\App\Actions\Auth\MeAction::class, 'index')
    ->auth();

PHP;

        if (file_put_contents($routesFile, $content . $authRoutes)) {
            $this->info('✓ Routes registered: routes/api.php');
        } else {
            throw new \RuntimeException('Failed to register routes.');
        }
    }
}
