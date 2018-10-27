<?php

namespace AEngine\Orchid\Annotations\Interfaces;

use BadMethodCallException;

interface AnnotationInterface
{
    /**
     * Return value for a key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Set value for a key
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set($key, $value);

    /**
     * Set values
     *
     * @param array $data
     *
     * @return $this
     */
    public function replace(array $data);

    /**
     * Error handler for unknown property accessor in Annotation class
     *
     * @param string $key Unknown property name
     *
     * @throws BadMethodCallException
     */
    public function __get($key);

    /**
     * Error handler for unknown property mutator in Annotation class
     *
     * @param string $key   Unknown property name
     * @param mixed  $value Property value
     *
     * @throws BadMethodCallException
     */
    public function __set($key, $value);
}
