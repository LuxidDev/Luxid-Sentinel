<?php

namespace Luxid\Console\Commands;

use Luxid\Console\Command;

class VersionCommand extends Command
{
    protected string $description = 'Show Luxid version';

    public function handle(array $argv): int
    {
        $this->parseArguments($argv);

        $juiceVersion = '1.0.0';

        // load engine from composer.json
        $composerFile = __DIR__ . '/../../../composer.json';
        $engineVersion = 'unknown';

        if (file_exists($composerFile)) {
            $json = json_decode(file_get_contents($composerFile), true);

            if (isset($json['version'])) {
                $engineVersion = $json['version'];
            }

        }

        $this->line("🍋 \033[1;36mJuice CLI v{$juiceVersion}\033[0m");
        $this->line("📦 \033[1;33mLuxid Engine v{$engineVersion}\033[0m");
        $this->line("🐘 \033[1;35mPHP " . PHP_VERSION . "\033[0m");
        $this->line("⚡ \033[1;32m" . php_uname('s') . " " . php_uname('r') . "\033[0m");

        return 0;
    }
}
