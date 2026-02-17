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
            $this->publishMigration();
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
            $this->info('Config published: config/sentinel.php');
        } else {
            throw new \RuntimeException('Failed to publish config file.');
        }
    }

    protected function publishMigration(): void
    {
        $this->line('🗄️  Publishing migration...');

        $source = $this->stubsPath . '/migrations/create_users_table.php.stub';
        $timestamp = date('YmdHis');
        $target = $this->projectRoot . "/migrations/{$timestamp}_create_users_table.php";

        $this->ensureDirectory(dirname($target));

        if (file_exists($target) && !$this->shouldForce()) {
            $this->warning('Migration file already exists. Skipping...');
            return;
        }

        $content = str_replace(
            '{{timestamp}}',
            $timestamp,
            file_get_contents($source)
        );

        if (file_put_contents($target, $content)) {
            $this->info("Migration published: migrations/{$timestamp}_create_users_table.php");
        } else {
            throw new \RuntimeException('Failed to publish migration file.');
        }
    }

    protected function generateEntity(): void
    {
        $this->line('👤 Generating User entity...');

        $targetDir = $this->projectRoot . '/app/Entities';
        $this->ensureDirectory($targetDir);

        $source = $this->stubsPath . '/entities/User.php.stub';
        $target = $targetDir . '/User.php';

        if (file_exists($target) && !$this->shouldForce()) {
            $this->warning('User entity already exists. Skipping...');
            return;
        }

        $content = str_replace(
            ['{{namespace}}', '{{tableName}}', '{{primaryKey}}'],
            ['App\\Entities', 'users', 'id'],
            file_get_contents($source)
        );

        if (file_put_contents($target, $content)) {
            $this->info('Entity generated: app/Entities/User.php');
        } else {
            throw new \RuntimeException('Failed to generate User entity.');
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
                $this->info("Action generated: app/Actions/Auth/{$action}.php");
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
            $this->info('Routes registered: routes/api.php');
        } else {
            throw new \RuntimeException('Failed to register routes.');
        }
    }
}
