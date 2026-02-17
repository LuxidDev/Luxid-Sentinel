<?php

namespace Luxid\Http;

interface SessionInterface
{
    public function get($key);
    public function set($key, $value);
    public function remove($key);
    public function setFlash($key, $message);
    public function getFlash($key);
    public function isStarted(): bool;
}
