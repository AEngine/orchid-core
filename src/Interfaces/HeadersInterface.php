<?php

namespace AEngine\Orchid\Interfaces;

/**
 * Headers Interface
 */
interface HeadersInterface extends CollectionInterface
{
    public function add($key, $value);

    public function normalizeKey($key);
}
