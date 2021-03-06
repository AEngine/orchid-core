<?php

namespace AEngine\Orchid\Support;

use AEngine\Orchid\Traits\Macroable;

class Crypta
{
    use Macroable;

    /**
     * Encrypt transmitted string
     *
     * @param string $input
     *
     * @return string
     */
    public static function encrypt($input)
    {
        return base64_encode(static::crypt($input));
    }

    /**
     * Helper method to work with a string line
     *
     * @param string $input
     *
     * @return string
     */
    protected static function crypt($input)
    {
        $salt = md5(app()->getSecret());
        $len = mb_strlen($input);
        $gamma = '';
        $n = $len > 100 ? 8 : 2;
        while (mb_strlen($gamma) < $len) {
            $gamma .= substr(pack('H*', sha1($gamma . $salt)), 0, $n);
        }

        return $input ^ $gamma;
    }

    /**
     * Decrypt passed string
     *
     * @param string $input
     *
     * @return string
     */
    public static function decrypt($input)
    {
        return static::crypt(base64_decode($input));
    }

    /**
     * Generate hash sum for a row
     *
     * @param string $string
     *
     * @return string
     */
    public static function hash($string)
    {
        $salt = substr(hash('whirlpool', uniqid(mt_rand() . app()->getSecret(), true)), 0, 12);
        $hash = hash('whirlpool', $salt . $string);
        $saltPos = (mb_strlen($string) >= mb_strlen($hash) ? mb_strlen($hash) : mb_strlen($string));

        return substr($hash, 0, $saltPos) . $salt . substr($hash, $saltPos);
    }

    /**
     * Check string against the hash sum
     *
     * @param string $string
     * @param string $hashString
     *
     * @return bool
     */
    public static function check($string, $hashString)
    {
        $saltPos = (mb_strlen($string) >= mb_strlen($hashString) ? mb_strlen($hashString) : mb_strlen($string));
        $salt = substr($hashString, $saltPos, 12);
        $hash = hash('whirlpool', $salt . $string);

        return $hashString == substr($hash, 0, $saltPos) . $salt . substr($hash, $saltPos);
    }

}
