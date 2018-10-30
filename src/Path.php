<?php

namespace AEngine\Orchid;

class Path
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var Container
     */
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Add path shortcut
     *
     * @param $shortcut
     * @param $path
     *
     * @return $this
     */
    public function add($shortcut, $path)
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        if (!isset($this->items[$shortcut])) {
            $this->items[$shortcut] = [];
        }

        array_unshift($this->items[$shortcut], is_file($path) ? $path : $path . '/');

        return $this;
    }

    /**
     * Get path by shortcut
     *
     * @param $path
     *
     * @return string|false
     */
    public function get($path)
    {
        if (static::isAbsolute($path) && file_exists($path)) {
            return $path;
        }

        if (($parts = explode(':', $path, 2)) && count($parts) == 2) {
            if (isset($this->items[$parts[0]])) {
                foreach ($this->items[$parts[0]] as &$path) {
                    if (file_exists($path . $parts[1])) {
                        return $path . $parts[1];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Return path list by shortcut
     *
     * @param $shortcut
     *
     * @return array
     */
    public function list($shortcut)
    {
        if ($shortcut) {
            return $this->items[$shortcut] ?? [];
        }

        return $this->items;
    }

    /**
     * Checks is absolute path
     *
     * @param string $path
     *
     * @return bool
     */
    public function isAbsolute($path)
    {
        return $path && (
                '/' == $path[0] ||
                '\\' == $path[0] ||
                (3 < mb_strlen($path) && ctype_alpha($path[0]) && $path[1] == ':' &&
                    (
                        '\\' == $path[2] ||
                        '/' == $path[2]
                    )
                )
            );
    }

    /**
     * Convert shortcut to uri
     *
     * @param $path
     *
     * @return bool|string
     */
    public function toUrl($path)
    {
        if (($file = $this->get($path)) != false) {
            return '/' . ltrim(str_replace($this->container->getBaseDir(), '', $file), '/');
        }

        return false;
    }

    /**
     * Support old usage variant
     *
     * @param $shortcut
     * @param $path
     *
     * @return App|bool|string
     * @deprecated
     */
    public function __invoke($shortcut, $path = '')
    {
        if ($shortcut && $path) {
            return $this->add($shortcut, $path);
        }

        return $this->get($shortcut);
    }
}
