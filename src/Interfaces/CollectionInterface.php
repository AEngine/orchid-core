<?php

namespace AEngine\Orchid\Interfaces;

interface CollectionInterface extends \ArrayAccess, \Countable, \Iterator
{
    public function get($key, $default = null);

    public function set($key, $value);

    public function replace(array $items);

    public function all();

    public function has($key);

    public function remove($key);

    public function clear();

    public function __toString();
}
