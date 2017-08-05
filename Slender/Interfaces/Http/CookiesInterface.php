<?php

namespace Slender\Interfaces\Http;

/**
 * Cookies Interface
 */
interface CookiesInterface
{
    public function get(string $name, $default = null);
    public function set(string $name, $value);
    public function toHeaders();
    public static function parseHeader($header): array;
}
