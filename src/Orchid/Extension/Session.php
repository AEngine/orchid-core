<?php

namespace AEngine\Orchid\Extension;

class Session
{
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
    public static function write($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Return data from the current session of the given key
     *
     * @param string $key
     * @param string $default
     *
     * @return Mixed
     */
    public static function read($key, $default = null)
    {
        if (array_key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }

        return $default;
    }

    /**
     * Removes data from the current session of the given key
     *
     * @param string $key
     */
    public static function delete($key)
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
