<?php

namespace AEngine\Orchid\Support;

use AEngine\Orchid\Exception\FileNotFoundException;
use AEngine\Orchid\Exception\NullPointException;
use AEngine\Orchid\Traits\Macroable;
use SplPriorityQueue;

class i18n
{
    use Macroable;

    /**
     * Buffer storage of the language file
     *
     * @var array
     */

    public static $locale = [];

    /**
     * Locale code
     *
     * @var string
     */
    public static $localeCode = null;

    /**
     * i18n constructor
     *
     * @param array $config
     *
     * @throws FileNotFoundException
     */
    public static function setup(array $config = [])
    {
        $default = [
            'accept' => [],
            'locale' => null,
            'default' => null,
            'force' => null,
        ];
        $config = array_merge($default, $config);
        $priority = new SplPriorityQueue();

        if ($config['force'] && in_array($config['force'], $config['accept'])) {
            $priority->insert($config['force'], 10);
        }

        if ($config['locale'] && in_array($config['locale'], $config['accept'])) {
            $priority->insert($config['locale'], 5);
        }

        if ($config['default'] && in_array($config['default'], $config['accept'])) {
            $priority->insert($config['default'], 0);
        }

        if (!count($priority)) {
            throw new NullPointException('Locale list is empty');
        }

        static::$locale = static::load($priority);
    }

    /**
     * Load language file for specified local
     *
     * @param SplPriorityQueue $priority
     *
     * @return array
     * @throws FileNotFoundException
     */
    protected static function load($priority)
    {
        while ($locale = $priority->extract()) {
            foreach (['php', 'ini'] as $type) {
                $path = app()->path('lang:' . trim($locale) . '.' . $type);

                if ($path) {
                    static::$localeCode = $locale;

                    switch ($type) {
                        case 'ini':
                            return parse_ini_file($path, true);
                        case 'php':
                            return require_once $path;
                    }
                }
            }
        }

        throw new FileNotFoundException('Could not find a language file');
    }

    /**
     * Get language code from header
     *
     * @param string $header
     * @param string $default
     *
     * @return mixed
     */
    public static function getLanguageFromHeader($header, $default = null)
    {
        preg_match_all('~(?<lang>\w+(?:\-\w+|))(?:\;q=(?<q>\d(?:\.\d|))|)[\,]{0,}~i', $header, $list);

        $data = [];
        foreach (array_combine($list['lang'], $list['q']) as $key => $priority) {
            $data[$key] = (float)($priority ? $priority : 1);
        }
        arsort($data, SORT_NUMERIC);

        return $data ? key($data) : $default;
    }
}
