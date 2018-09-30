<?php

namespace AEngine\Orchid;

use Closure;
use AEngine\Orchid\Interfaces\RouteGroupInterface;

class RouteGroup implements RouteGroupInterface
{
    /**
     * The route URI pattern
     *
     * @var string
     */
    protected $pattern = '';

    /**
     * The callable payload
     *
     * @var callable
     */
    protected $callable;

    /**
     * Create a new RouteGroup
     *
     * @param string   $pattern The pattern prefix for the group
     * @param callable $callable The group callable
     */
    public function __construct($pattern, $callable)
    {
        $this->pattern  = $pattern;
        $this->callable = $callable;
    }

    /**
     * Get route callable
     *
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * This method enables you to override the Route's callable
     *
     * @param string|Closure $callable
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;
    }

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Invoke the group to register any Route objects within it
     */
    public function __invoke()
    {
        call_user_func($this->callable);
    }
}
