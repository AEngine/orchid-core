<?php

namespace AEngine\Orchid\Support;

use AEngine\Orchid\Exception\FileNotFoundException;
use AEngine\Orchid\Traits\Macroable;
use AEngine\Orchid\View;
use DirectoryIterator;

class Asset
{
    use Macroable;

    /**
     * Resource map
     *
     * @var array
     */
    public static $map = [];

    /**
     * Generates resource string based on the resources map
     *
     * @return null|string
     */
    public static function resource()
    {
        $include = [];

        if (static::$map) {
            $pathname = '/' . ltrim(app()->request()->getUri()->getPath(), '/');
            $uri = explode('/', $pathname);
            array_shift($uri); // remove first slash

            // search masks
            foreach (static::$map as $mask => $map) {
                if (is_array($map)) {
                    if ($mask === $pathname) {
                        $include = array_merge($include, static::resourceIterator($map));
                        continue;
                    }

                    /* #\.html$# */
                    if (substr($mask, 0, 1) == '#' && substr($mask, -1) == '#') {
                        if (preg_match($mask, $pathname, $match)) {
                            $include = array_merge($include, static::resourceIterator($map));
                            continue;
                        }
                    }

                    /* /example/* */
                    if (strpos($mask, '*') !== false) {
                        $pattern = '#^' . str_replace('\\*', '(.*)', preg_quote($mask, '#')) . '#';
                        if (preg_match($pattern, $pathname, $match)) {
                            $include = array_merge($include, static::resourceIterator($map));
                            continue;
                        }
                    }

                    /* /example/:id */
                    if (strpos($mask, ':') !== false) {
                        $parts = explode('/', $mask);
                        array_shift($parts);

                        if (count($uri) == count($parts)) {
                            $matched = true;

                            foreach ($parts as $index => $part) {
                                if (':' !== substr($part, 0, 1) && $uri[$index] != $parts[$index]) {
                                    $matched = false;
                                    break;
                                }
                            }

                            if ($matched) {
                                $include = array_merge($include, static::resourceIterator($map));
                                continue;
                            }
                        }
                    }
                }
            }

            // previous checks have failed
            if (empty($include)) {
                $include = static::resourceIterator(static::$map);
            }
        }

        return $include ? implode("\n", $include) : null;
    }

    /**
     * Bypasses the passed array and returns an array of strings to connect resources
     *
     * @param array $list
     *
     * @return array
     */
    protected static function resourceIterator(array $list)
    {
        $include = [];

        foreach ($list as $address) {
            switch (pathinfo($address)['extension']) {
                case 'js':
                    $include[] = '<script type="text/javascript" src="' . $address . '"></script>';
                    break;
                case 'css':
                    $include[] = '<link rel="stylesheet" type="text/css" href="' . $address . '" />';
                    break;
                case 'less':
                    $include[] = '<link rel="stylesheet/less" type="text/css" href="' . $address . '" />';
                    break;
            }
        }

        return $include;
    }

    /**
     * Collect all of the templates from folder 'template' and of all loaded modules
     *
     * @return string
     * @throws FileNotFoundException
     */
    public static function template()
    {
        $app = app();
        $template = [];

        // catalog manually from the templates
        foreach (app()->pathList('template') as $path) {
            $template = array_merge($template, static::templateIterator($path));
        }

        // modules that have templates
        foreach (app()->getModules() as $module) {
            if ($path = $app->path($module . ':template')) {
                $template = array_merge($template, static::templateIterator($path));
            }
        }

        return $template ? implode("\n", $template) : null;
    }

    /**
     * Recursively specified directory and collects templates
     *
     * @param string $dir
     * @param string $initial
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected static function templateIterator($dir, $initial = '')
    {
        $dir = realpath($dir);
        $template = [];

        foreach (new DirectoryIterator($dir) as $item) {
            if (!$item->isDot()) {
                if ($item->isDir()) {
                    $template = array_merge(
                        $template,
                        static::templateIterator(
                            app()->path($dir . '/' . $item->getBasename()),
                            $initial ? $initial : $dir
                        )
                    );
                } else {
                    if ($item->isFile()) {
                        $file = realpath($item->getPathname());
                        $ext = pathinfo($file)['extension'];
                        if (in_array($ext, ['tpl', 'ejs'])) {
                            $name = str_replace(
                                ['/', '.tpl', '.ejs'],
                                ['-', '', ''],
                                explode($initial ? $initial : $dir, $file)[1]
                            );

                            switch ($ext) {
                                case 'tpl':
                                    $template[] = '<script id="tpl' . $name . '" type="text/template">' .
                                        View::fetch($file) . '</script>';
                                    break;
                                case 'ejs':
                                    $template[] = '<script id="tpl' . $name . '" type="text/template">' .
                                        file_get_contents($file) . '</script>';
                                    break;
                            }
                        }
                    }
                }
            }
        }

        return $template;
    }
}
