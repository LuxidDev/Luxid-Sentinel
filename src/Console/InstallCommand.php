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
            $this->generateEntity();
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
            $this->info('✓ Config published: config/sentinel.php');
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
            $this->info('✓ remember_token already exists in migration');
            return;
        }

        // Add remember_token column to the existing migration
        $pattern = '/(\s*\)\s*ENGINE\s*=\s*InnoDB.*;)/';
        $rememberTokenColumn = ",\n            remember_token VARCHAR(100) NULL,\n            INDEX idx_remember_token (remember_token)";
        $newContent = preg_replace($pattern, $rememberTokenColumn . '$1', $content);

        if ($newContent && $newContent !== $content) {
            if (file_put_contents($migrationFile, $newContent)) {
                $this->info("✓ Updated migration: {$usersMigration} (added remember_token column)");
            } else {
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
        $className = sprintf('m%05d_create_users_table', $nextNumber);
        $content = str_replace('{{class}}', $className, $content);

        if (file_put_contents($target, $content)) {
            $this->info("✓ Migration created: migrations/{$filename}");
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
     * Generate User entity from stub (replaces existing if --force is used)
     */
    protected function generateEntity(): void
    {
        $this->line('👤 Setting up User entity...');

        $targetDir = $this->projectRoot . '/app/Entities';
        $this->ensureDirectory($targetDir);

        $target = $targetDir . '/User.php';
        $source = $this->stubsPath . '/entities/User.php.stub';

        // Check if file exists and we're not forcing
        if (file_exists($target) && !$this->shouldForce()) {
            $this->warning('User entity already exists. Use --force to replace it.');
            return;
        }

        // Generate new User entity from stub
        $content = file_get_contents($source);
        $content = str_replace(
            ['{{namespace}}', '{{tableName}}', '{{primaryKey}}'],
            ['App\\Entities', 'users', 'id'],
            $content
        );

        if (file_put_contents($target, $content)) {
            $this->info('✓ User entity created: app/Entities/User.php');
        } else {
            throw new \RuntimeException('Failed to create User entity.');
        }
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
