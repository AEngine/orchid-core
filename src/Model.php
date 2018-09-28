<?php

namespace AEngine\Orchid;

use AEngine\Orchid\Interfaces\ModelInterface;
use BadMethodCallException;

abstract class Model implements ModelInterface
{
    /**
     * Model constructor
     *
     * @param array $data
     */
    final public function __construct(array $data = [])
    {
        $this->replace($data);
    }

    /**
     * Return value for a key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->$key;
    }

    /**
     * Set value for a key
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $this->$key = $value;

        return $this;
    }

    /**
     * Set values for all keys
     *
     * @param array $data
     *
     * @return $this
     */
    public function replace(array $data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * Check has key
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->$key);
    }

    /**
     * Check whether the key is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        foreach (get_object_vars($this) as $value) {
            if (!empty($value)) return false;
        }

        return true;
    }

    /**
     * Restore default value for key
     *
     * @param string $key
     *
     * @return $this
     */
    public function delete($key)
    {
        $this->$key = null;

        return $this;
    }

    /**
     * Restore default model data
     *
     * @return $this
     */
    public function clear()
    {
        foreach (get_object_vars($this) as $key => $value) {
            $this->$key = null;
        }

        return $this;
    }

    /**
     * Return model as array
     *
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Return model as string
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(get_object_vars($this), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Error handler for unknown property accessor in Annotation class
     *
     * @param string $key Unknown property name
     *
     * @throws BadMethodCallException
     */
    public function __get($key)
    {
        throw new BadMethodCallException(
            sprintf("Unknown property '%s' in class '%s'.", $key, get_class($this))
        );
    }

    /**
     * Error handler for unknown property mutator in Annotation class
     *
     * @param string $key   Unknown property name
     * @param mixed  $value Property value
     *
     * @throws BadMethodCallException
     */
    public function __set($key, $value)
    {
        throw new BadMethodCallException(
            sprintf("Unknown property '%s' in class '%s'.", $key, get_class($this))
        );
    }
}
