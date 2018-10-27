<?php

namespace AEngine\Orchid\Annotations;

use AEngine\Orchid\Annotations\Interfaces\AnnotationInterface;
use BadMethodCallException;

abstract class Annotation implements AnnotationInterface
{
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
     * Set values
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
