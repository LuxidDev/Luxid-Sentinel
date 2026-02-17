<?php

namespace Luxid\Http;

class Session implements SessionInterface
{
    protected const FLASH_KEY = 'flash_messages';
    protected bool $started = false;
    protected bool $isCLI;

    public function __construct()
    {
        $this->isCLI = php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';

        if (!$this->isCLI && !headers_sent()) {
            session_start();
            $this->started = true;
            $this->initializeFlashMessages();
        }
    }

    protected function initializeFlashMessages(): void
    {
        if (!$this->started) {
            return;
        }

        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            $flashMessage['remove'] = true;
        }

        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    public function setFlash($key, $message)
    {
        if ($this->started) {
            $_SESSION[self::FLASH_KEY][$key] = [
                'removed' => false,
                'value' => $message
            ];
        }
    }

    public function getFlash($key)
    {
        if ($this->started) {
            return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
        }
        return false;
    }

    public function set($key, $value)
    {
        if ($this->started) {
            $_SESSION[$key] = $value;
        }
    }

    public function get($key)
    {
        if ($this->started) {
            return $_SESSION[$key] ?? null;
        }
        return null;
    }

    public function remove($key)
    {
        if ($this->started) {
            unset($_SESSION[$key]);
        }
    }

    public function __destruct()
    {
        if ($this->started) {
            $this->cleanupFlashMessages();
        }
    }

    protected function cleanupFlashMessages(): void
    {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            if ($flashMessage['remove']) {
                unset($flashMessages[$key]);
            }
        }

        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    public function isStarted(): bool
    {
        return $this->started;
    }
}
