<?php

namespace AEngine\Orchid\Interfaces;

/**
 * Cookies Interface
 */
interface CookiesInterface
{
    public static function parseHeader($header);

    public function get($name, $default = null);

    public function set($name, $value);

    public function toHeaders();
}
