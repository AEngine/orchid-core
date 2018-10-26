<?php

namespace AEngine\Orchid\Support;

use AEngine\Orchid\Traits\Macroable;

class Str
{
    use Macroable;

    /**
     * Return the remainder of a string after a given value.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    public static function after($subject, $search)
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * Get the portion of a string before a given value.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    public static function before($subject, $search)
    {
        return $search === '' ? $subject : explode($search, $subject)[0];
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return true if the string starts with the specified character
     *
     * @param string|array $needles
     * @param string       $haystack
     *
     * @return bool
     */
    public static function start($needles, $haystack)
    {
        foreach ((array)$needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string)$needle) {
                return true;
            }
        }

        return false;
    }


    /**
     * Return true if the string ends with the specified character
     *
     * @param string|array $needles
     * @param string       $haystack
     *
     * @return bool
     */
    public static function end($needles, $haystack)
    {
        foreach ((array)$needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string)$needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * It is safe to truncate to the specified value, without losing the last word
     *
     * @param string $value
     * @param int    $limit
     * @param string $end
     *
     * @return string
     */
    public static function truncate($value, $limit, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

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
    public static function eos($count, $one, $two, $five)
    {
        if (substr($count, -1, 1) == '1' && substr($count, -2, 2) != '11') {
            return $one;
        } else {
            if (substr($count, -2, 1) != 1 && substr($count, -1, 1) > 1 && substr($count, -1, 1) < 5) {
                return $two;
            } else {
                return $five;
            }
        }
    }

    /**
     * Convert the given string to lower-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Convert the given string to upper-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Convert the given string to title case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert Arabic number to Roman
     *
     * @param integer $int
     *
     * @return string
     */
    public static function int2roman($int)
    {
        $romanNumerals = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1,
        ];

        $result = '';
        foreach ($romanNumerals as $roman => $number) {
            $result .= str_repeat($roman, intval($int / $number));
            $int = $int % $number;
        }

        return $result;
    }

    /**
     * Escape a string or an array of strings
     *
     * @param string|array $input
     *
     * @return string;
     */
    public static function escape($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = static::escape($value);
            }
        } else {
            $input = htmlspecialchars($input, ENT_QUOTES);
        }

        return $input;
    }

    /**
     * Remove the screening in a row or an array of strings
     *
     * @param string|array $input
     *
     * @return string;
     */
    public static function unEscape($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = static::unEscape($value);
            }
        } else {
            $input = htmlspecialchars_decode($input, ENT_QUOTES);
        }

        return $input;
    }
}
