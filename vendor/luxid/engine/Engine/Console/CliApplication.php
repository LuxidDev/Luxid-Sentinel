<?php

namespace Luxid\Console;

use Luxid\Foundation\Application;

class CliApplication extends Application
{
    // Override the typed properties to be nullable
    public ?\Luxid\Database\Database $db = null;
    public ?\Luxid\Database\DbEntity $user = null;

    public function __construct($rootPath, array $config)
    {
        $this->userClass = $config['userClass'];

        Application::$ROOT_DIR = $rootPath;
        Application::$app = $this;

        $this->request = new \Luxid\Http\Request();
        $this->response = new \Luxid\Http\Response();

        // Create null session for CLI
        $this->session = new \Luxid\Http\NullSession();

        $this->router = new \Luxid\Routing\Router($this->request, $this->response);
        $this->screen = new \Luxid\Foundation\Screen();

        // Don't initialize database for CLI - keep as null
        // $this->db and $this->user remain null
    }

    // Override any methods that might use $db
    public static function isGuest(): bool
    {
        // In CLI mode, always return true (guest)
        return true;
    }

    public function login(\Luxid\Database\DbEntity $user): bool
    {
        // No-op in CLI
        return true;
    }

    public function logout(): void
    {
        // No-op in CLI
    }
}
