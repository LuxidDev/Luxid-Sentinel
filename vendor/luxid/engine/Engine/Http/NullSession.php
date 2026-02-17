<?php

namespace Luxid\Http;

class NullSession implements SessionInterface
{
    public function __construct() {}
    public function get($key) { return null; }
    public function set($key, $value) {}
    public function remove($key) {}
    public function setFlash($key, $message) {}
    public function getFlash($key) { return false; }
    public function isStarted(): bool { return false; }
}
