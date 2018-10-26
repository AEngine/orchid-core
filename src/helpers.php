<?php

use AEngine\Orchid\Collection;
use AEngine\Orchid\Support\Arr;
use AEngine\Orchid\Support\Str;

if (!function_exists('pre')) {
    /**
     * Function wrapper around var_dump for debugging
     *
     * @param mixed ...$args
     */
    function pre(...$args)
    {
        echo '<pre>';
        foreach ($args as $obj) {
            var_dump($obj);
        }
        echo '</pre>';
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param mixed $value
     *
     * @return Collection
     */
    function collect($value = null)
    {
        return new Collection($value);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } else if (!is_array($target)) {
                    return value($default);
                }
                $result = [];
                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } else if (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}
if (!function_exists('data_set')) {
    /**
     * Set an item on an array or object using dot notation.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $value
     * @param bool         $overwrite
     *
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (!Arr::accessible($target)) {
                $target = [];
            }
            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } else if ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } else if (Arr::accessible($target)) {
            if ($segments) {
                if (!Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }
                data_set($target[$segment], $segments, $value, $overwrite);
            } else if ($overwrite || !Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } else if (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                data_set($target->{$segment}, $segments, $value, $overwrite);
            } else if ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } else if ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('str_after')) {
    /**
     * Return the remainder of a string after a given value.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    function str_after($subject, $search)
    {
        return Str::after($subject, $search);
    }
}
if (!function_exists('str_before')) {
    /**
     * Get the portion of a string before a given value.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    function str_before($subject, $search)
    {
        return Str::before($subject, $search);
    }
}

if (!function_exists('str_contains')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        return Str::contains($haystack, $needles);
    }
}

if (!function_exists('str_starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    function str_starts_with($haystack, $needles)
    {
        return Str::start($haystack, $needles);
    }
}

if (!function_exists('str_ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    function str_ends_with($haystack, $needles)
    {
        return Str::end($haystack, $needles);
    }
}

if (!function_exists('str_truncate')) {
    /**
     * Limit the number of characters in a string.
     *
     * @param string $value
     * @param int    $limit
     * @param string $end
     *
     * @return string
     */
    function str_truncate($value, $limit = 100, $end = '...')
    {
        return Str::truncate($value, $limit, $end);
    }
}

if (!function_exists('str_truncate')) {
    /**
     * Slope of the word, depending on the number
     *
     * @param int    $count
     * @param string $one
     * @param string $two
     * @param string $five
     *
     * @return string
     */
    function str_eos($count, $one, $two, $five)
    {
        return Str::eos($count, $one, $two, $five);
    }
}

if (! function_exists('str_title_case')) {
    /**
     * Convert a value to title case.
     *
     * @param  string  $value
     * @return string
     */
    function str_title_case($value)
    {
        return Str::title($value);
    }
}

if (! function_exists('str_escape')) {
    /**
     * Escape a string or an array of strings
     *
     * @param string|array $input
     *
     * @return string;
     */
    function str_escape($input)
    {
        return Str::escape($input);
    }
}

if (! function_exists('str_un_escape')) {
    /**
     * Remove the screening in a row or an array of strings
     *
     * @param string|array $input
     *
     * @return string;
     */
    function str_un_escape($input)
    {
        return Str::unEscape($input);
    }
}
