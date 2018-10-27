<?php

namespace AEngine\Orchid\Support;

use AEngine\Orchid\Traits\Macroable;

class Session
{
    use Macroable;

    /**
     * Create new session with the given name
     *
     * @param string $name
     */
    public static function create($name = 'session')
    {
        if (!mb_strlen(session_id())) {
            session_name($name);
            session_start();
        }
    }

    /**
     * Writes the data in the current session
     *
     * @param string $key
     * @param string $value
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Return data from the current session of the given key
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (array_key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }

        return value($default);
    }

    /**
     * Removes data from the current session of the given key
     *
     * @param string $key
     */
    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroys the current session
     */
    public static function destroy()
    {
        session_destroy();
    }
}
