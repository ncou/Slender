<?php

namespace Slender\Interfaces\Http;

use Slender\Interfaces\CollectionInterface;

/**
 * Headers Interface
 */
interface HeadersInterface extends CollectionInterface
{
    public function add(string $key, $value);

    public function normalizeKey(string $key);
}
