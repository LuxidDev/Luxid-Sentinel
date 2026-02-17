<?php

namespace Luxid\Foundation;

class Screen
{
    public string $title = '';

    public function renderScreen($screen, $data = [])
    {
        $screenContent = $this->renderOnlyScreen($screen, $data);
        $frameContent = $this->frameContent();

        return str_replace('|| content ||', $screenContent, $frameContent);
    }

    public function renderContent($screenContent)
    {
        $frameContent = $this->frameContent();

        return str_replace('|| content ||', $screenContent, $frameContent);
    }

    protected function frameContent()
    {
        $frame = Application::$app->frame;
        if (Application::$app->action) {
            $frame = Application::$app->action->frame;
        }

        ob_start();   // basically starts the output caching - so nothing get outputted on the browser
        include_once Application::$ROOT_DIR . "/screens/frames/$frame.nova.php";

        return ob_get_clean();    // returns whatever that's in th buffer and clears it
    }

    protected function renderOnlyScreen($screen, $data)
    {
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        ob_start();
        include_once Application::$ROOT_DIR . "/screens/$screen.nova.php";
        return ob_get_clean();
    }
}
