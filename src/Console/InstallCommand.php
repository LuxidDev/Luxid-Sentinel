<?php

declare(strict_types=1);

namespace Luxid\Sentinel\Console;

use Luxid\Console\Command;

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

    $this->line('🔐 Installing Luxid Sentinel...');
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
      $this->createMigration();
      $this->generateEntity();
      $this->generateAuthAction();
      $this->registerRoutes();

      $this->success('Sentinel installed successfully!');
      $this->line('');
      $this->line('📋 Next steps:');
      $this->line('   1. Review config/sentinel.php');
      $this->line('   2. Run: php juice db:migrate');
      $this->line('   3. Test your auth endpoints:');
      $this->line('      POST   /auth/register');
      $this->line('      POST   /auth/login');
      $this->line('      POST   /auth/logout');
      $this->line('      GET    /auth/me');
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

    $source = $this->stubsPath . '/sentinel.php.stub';
    $target = $this->projectRoot . '/config/sentinel.php';

    $this->ensureDirectory(dirname($target));

    if (file_exists($target) && !$this->shouldForce()) {
      $this->warning('Config file already exists. Skipping...');
      return;
    }

    $content = str_replace(
      ['{{userClass}}'],
      ['App\\Entities\\User'],
      file_get_contents($source)
    );

    if (file_put_contents($target, $content)) {
      $this->info('✓ Config published: config/sentinel.php');
    } else {
      throw new \RuntimeException('Failed to publish config file.');
    }
  }

  protected function createMigration(): void
  {
    $this->line('📝 Creating users table migration...');

    $migrationsPath = $this->projectRoot . '/migrations';
    $this->ensureDirectory($migrationsPath);

    // Find existing users table migration
    $existingFiles = glob($migrationsPath . '/m*_create_users_table.php');

    // Remove existing users table migration if found
    foreach ($existingFiles as $existingFile) {
      if (unlink($existingFile)) {
        $this->info("✓ Removed existing migration: " . basename($existingFile));
      }
    }

    $nextNumber = $this->getNextMigrationNumber();
    $filename = sprintf('m%05d_create_users_table.php', $nextNumber);
    $target = $migrationsPath . '/' . $filename;

    $source = $this->stubsPath . '/create_users_table.php.stub';

    $className = sprintf('m%05d_create_users_table', $nextNumber);
    $content = str_replace('{{class}}', $className, file_get_contents($source));

    if (file_put_contents($target, $content)) {
      $this->info("✓ Migration created: migrations/{$filename}");
    } else {
      throw new \RuntimeException('Failed to create migration.');
    }
  }

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

  protected function generateEntity(): void
  {
    $this->line('👤 Setting up User entity...');

    $targetDir = $this->projectRoot . '/app/Entities';
    $this->ensureDirectory($targetDir);

    $target = $targetDir . '/User.php';
    $source = $this->stubsPath . '/User.php.stub';

    // Always replace the entity (with or without force)
    if (file_exists($target)) {
      $this->info("✓ Replacing existing User entity...");
    }

    if (file_put_contents($target, file_get_contents($source))) {
      $this->info('✓ User entity created: app/Entities/User.php');
    } else {
      throw new \RuntimeException('Failed to create User entity.');
    }
  }

  protected function generateAuthAction(): void
  {
    $this->line('⚡ Generating authentication action...');

    $targetDir = $this->projectRoot . '/app/Actions';
    $this->ensureDirectory($targetDir);

    $target = $targetDir . '/AuthAction.php';
    $source = $this->stubsPath . '/AuthAction.php.stub';

    if (file_exists($target) && !$this->shouldForce()) {
      $this->warning('AuthAction already exists. Skipping...');
      return;
    }

    if (file_put_contents($target, file_get_contents($source))) {
      $this->info('✓ AuthAction created: app/Actions/AuthAction.php');
    } else {
      throw new \RuntimeException('Failed to create AuthAction.');
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

    if (strpos($content, 'AuthAction') !== false) {
      $this->warning('Auth routes already registered. Skipping...');
      return;
    }

    $authRoutes = <<<'PHP'

// Authentication Routes (Sentinel)
route('auth.register')
    ->post('/auth/register')
    ->uses(\App\Actions\AuthAction::class, 'register')
    ->open();

route('auth.login')
    ->post('/auth/login')
    ->uses(\App\Actions\AuthAction::class, 'login')
    ->open();

route('auth.logout')
    ->post('/auth/logout')
    ->uses(\App\Actions\AuthAction::class, 'logout')
    ->auth();

route('auth.me')
    ->get('/auth/me')
    ->uses(\App\Actions\AuthAction::class, 'me')
    ->auth();

PHP;

    if (file_put_contents($routesFile, $content . $authRoutes)) {
      $this->info('✓ Routes registered: routes/api.php');
    } else {
      throw new \RuntimeException('Failed to register routes.');
    }
  }
}
