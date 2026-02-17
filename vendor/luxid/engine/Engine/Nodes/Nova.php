<?php
namespace Luxid\Nodes;

use Luxid\Foundation\Application;

class Nova
{
    /**
     * Get the current Screen instance
     */
    protected static function instance()
    {
        if (!Application::$app || !Application::$app->screen) {
            throw new \RuntimeException("Nova/Screen instance is not available.");
        }

        return Application::$app->screen;
    }

    /**
     * Render a screen with data
     */
    public static function render(string $screen, array $data = []): string
    {
        return self::instance()->renderScreen($screen, $data);
    }

    /**
     * Render just content without frame
     */
    public static function content(string $screenContent): string
    {
        return self::instance()->renderContent($screenContent);
    }
}
