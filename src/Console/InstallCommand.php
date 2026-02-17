<?php

declare(strict_types=1);

namespace Luxid\Sentinel\Console;

use Luxid\Console\Command;

/**
 * Install command for Sentinel authentication package.
 *
 * Publishes configuration, migrations, entities, and actions.
 * Run with: php juice sentinel:install
 *
 * @package Luxid\Sentinel\Console
 */
class InstallCommand extends Command
{
    /**
     * Command description.
     *
     * @var string
     */
    protected string $description = 'Install Sentinel authentication package';

    /**
     * Stubs directory path.
     *
     * @var string
     */
    protected string $stubsPath;

    /**
     * Project root path.
     *
     * @var string
     */
    protected string $projectRoot;

    /**
     * Create a new install command instance.
     */
    public function __construct()
    {
        parent::__construct();

        // Determine stubs path relative to this file
        $this->stubsPath = dirname(__DIR__, 2) . '/stubs';
        $this->projectRoot = $this->getProjectRoot();
    }

    /**
     * Handle the command execution.
     *
     * @param array $argv Command arguments
     * @return int Exit code
     */
    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $this->line('🍋 Installing Luxid Sentinel...');
        $this->line('');

        try {
            // Check if already installed
            if ($this->isInstalled() && !$this->shouldForce()) {
                $this->warning('Sentinel appears to be already installed.');
                if (!$this->confirm('Do you want to reinstall? This may overwrite existing files.')) {
                    $this->line('Installation cancelled.');
                    return 0;
                }
            }

            // Publish files
            $this->publishConfig();
            $this->publishMigration();
            $this->generateEntity();
            $this->generateActions();

            // Register routes
            $this->registerRoutes();

            $this->success('Sentinel installed successfully!');
            $this->line('');
            $this->line('📋 Next steps:');
            $this->line('   1. Review config/sentinel.php');
            $this->line('   2. Run: php juice db:migrate');
            $this->line('   3. Add the RequireAuth middleware to protected routes:');
            $this->line('');
            $this->line('      use Luxid\\Sentinel\\Middleware\\RequireAuth;');
            $this->line('');
            $this->line('      route("protected")');
            $this->line('          ->get("/dashboard")');
            $this->line('          ->uses(DashboardAction::class, "index")');
            $this->line('          ->with(new RequireAuth(auth(), app()->response));');
            $this->line('');
            $this->line('   4. Test your auth endpoints:');
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

    /**
     * Check if Sentinel is already installed.
     *
     * @return bool
     */
    protected function isInstalled(): bool
    {
        return file_exists($this->projectRoot . '/config/sentinel.php') ||
               file_exists($this->projectRoot . '/app/Entities/User.php');
    }

    /**
     * Check if force option is set.
     *
     * @return bool
     */
    protected function shouldForce(): bool
    {
        return isset($this->options['force']) || in_array('--force', $_SERVER['argv'] ?? []);
    }

    /**
     * Publish configuration file.
     *
     * @return void
     */
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

        $content = file_get_contents($source);

        // Replace placeholders if any
        $content = str_replace(
            ['{{namespace}}', '{{userClass}}'],
            ['App\\Entities', 'App\\Entities\\User'],
            $content
        );

        if (file_put_contents($target, $content)) {
            $this->info('Config published: config/sentinel.php');
        } else {
            throw new \RuntimeException('Failed to publish config file.');
        }
    }

    /**
     * Publish migration file.
     *
     * @return void
     */
    protected function publishMigration(): void
    {
        $this->line('🗄️  Publishing migration...');

        $source = $this->stubsPath . '/migrations/create_users_table.php.stub';

        // Generate timestamp for migration
        $timestamp = date('YmdHis');
        $target = $this->projectRoot . "/migrations/{$timestamp}_create_users_table.php";

        $this->ensureDirectory(dirname($target));

        if (file_exists($target) && !$this->shouldForce()) {
            $this->warning('Migration file already exists. Skipping...');
            return;
        }

        if (copy($source, $target)) {
            $this->info("Migration published: migrations/{$timestamp}_create_users_table.php");
        } else {
            throw new \RuntimeException('Failed to publish migration file.');
        }
    }

    /**
     * Generate User entity.
     *
     * @return void
     */
    protected function generateEntity(): void
    {
        $this->line('👤 Generating User entity...');

        $source = $this->stubsPath . '/entities/User.php.stub';
        $target = $this->projectRoot . '/app/Entities/User.php';

        $this->ensureDirectory(dirname($target));

        if (file_exists($target) && !$this->shouldForce()) {
            $this->warning('User entity already exists. Skipping...');
            return;
        }

        $content = file_get_contents($source);

        // Replace placeholders
        $content = str_replace(
            ['{{namespace}}', '{{tableName}}', '{{primaryKey}}'],
            ['App\\Entities', 'users', 'id'],
            $content
        );

        if (file_put_contents($target, $content)) {
            $this->info('Entity generated: app/Entities/User.php');
        } else {
            throw new \RuntimeException('Failed to generate User entity.');
        }
    }

    /**
     * Generate authentication actions.
     *
     * @return void
     */
    protected function generateActions(): void
    {
        $this->line('⚡ Generating authentication actions...');

        $actions = [
            'RegisterAction' => 'register',
            'LoginAction' => 'login',
            'LogoutAction' => 'logout',
            'MeAction' => 'me',
        ];

        foreach ($actions as $stubName => $actionName) {
            $source = $this->stubsPath . "/actions/Auth/{$stubName}.php.stub";
            $target = $this->projectRoot . "/app/Actions/Auth/{$stubName}.php";

            $this->ensureDirectory(dirname($target));

            if (file_exists($target) && !$this->shouldForce()) {
                $this->warning("Action {$stubName} already exists. Skipping...");
                continue;
            }

            $content = file_get_contents($source);

            // Replace placeholders
            $content = str_replace(
                ['{{namespace}}', '{{userClass}}'],
                ['App\\Actions\\Auth', 'App\\Entities\\User'],
                $content
            );

            if (file_put_contents($target, $content)) {
                $this->info("Action generated: app/Actions/Auth/{$stubName}.php");
            } else {
                throw new \RuntimeException("Failed to generate {$stubName}.");
            }
        }
    }

    /**
     * Register authentication routes.
     *
     * @return void
     */
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
