<?php

namespace AEngine\Orchid;

use Closure;
use AEngine\Orchid\Interfaces\RouteInterface;
use AEngine\Orchid\Exception\NoSuchMethodException;
use AEngine\Orchid\Message\Body;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use InvalidArgumentException;
use UnexpectedValueException;

class Route implements RouteInterface
{
    use MiddlewareTrait;

    /**
     * HTTP methods supported by this route
     *
     * @var string[]
     */
    protected $methods = [];

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
     * Parent route groups
     *
     * @var RouteGroup[]
     */
    protected $groups;

    /**
     * Route priority
     *
     * @var string
     */
    protected $priority;

    /**
     * Route identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * Route parameters
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Output buffering mode
     * One of: false, 'prepend' or 'append'
     *
     * @var boolean|string
     */
    protected $outputBuffering = 'prepend';

    /**
     * Create new route
     *
     * @param string|string[] $methods The route HTTP methods
     * @param string          $pattern The route pattern
     * @param array|Closure   $callable The route callable
     * @param int             $priority The route priority
     * @param RouteGroup[]    $groups The parent route groups
     * @param int             $identifier The route identifier
     */
    public function __construct($methods, $pattern, $callable, $priority = 0, $groups = [], $identifier = 0)
    {
        $this->methods    = is_string($methods) ? [$methods] : $methods;
        $this->pattern    = $pattern;
        $this->callable   = $callable;
        $this->groups     = $groups;
        $this->priority   = $priority;
        $this->identifier = 'route' . $identifier;
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
     * Get route methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
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
     * Get parent route groups
     *
     * @return RouteGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Get route priority
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Get route identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;

        return $this;
    }

    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getArgument($name = '', $default = null)
    {
        if ($name) {
            if (array_key_exists($name, $this->arguments)) {
                return $this->arguments[$name];
            }

            return $default;
        }

        return $this->arguments;
    }

    /**
     * Get output buffering mode
     *
     * @return boolean|string
     */
    public function getOutputBuffering()
    {
        return $this->outputBuffering;
    }

    /**
     * Set output buffering mode
     * One of: false, 'prepend' or 'append'
     *
     * @param boolean|string $mode
     *
     * @throws InvalidArgumentException If an unknown buffering mode is specified
     */
    public function setOutputBuffering($mode)
    {
        if (!in_array($mode, [false, 'prepend', 'append'], true)) {
            throw new InvalidArgumentException('Unknown output buffering mode');
        }
        $this->outputBuffering = $mode;
    }

    /**
     * Dispatch route callable against current Request and Response objects
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param ServerRequestInterface $request The current Request object
     * @param ResponseInterface      $response The current Response object
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Throwable  if the route callable throws an exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            ob_start();

            if (is_object($this->callable)) {
                $result = call_user_func_array($this->callable, [$request, $response, $this->getArgument()]);

                // if route callback returns a ResponseInterface, then use it
                if ($result instanceof ResponseInterface) {
                    $response = $result;
                } else {
                    // if route callback returns a string, then append it to the response
                    if (is_string($result) || method_exists($result, '__toString')) {
                        if ($response->getBody()->isWritable()) {
                            $response->getBody()->write($result);
                        }
                    }
                }
            } else {
                if (is_array($this->callable)) {
                    $controller = !empty($this->callable[0]) ? $this->callable[0] : '';
                    $action     = !empty($this->callable[1]) ? $this->callable[1] : 'index';
                } else {
                    $controller = $this->callable;
                    $action     = 'index';
                }

                // controller not found
                if (!$controller || !class_exists($controller)) {
                    throw new UnexpectedValueException(
                        'Route callable must be defined as array or string or object'
                    );
                }

                $callback = new $controller;

                // controller is not \AEngine\Orchid\Controller
                if ($callback && !is_a($callback, Controller::class)) {
                    throw new UnexpectedValueException(
                        'Controller must extend of \AEngine\Orchid\Controller'
                    );
                }

                foreach (['before', $action, 'after'] as $val) {
                    if ($val == 'before' || $callback->execute) {
                        if (method_exists($callback, $val)) {
                            $result = call_user_func_array(
                                [$callback, $val],
                                [$request, $response, $this->getArgument()]
                            );

                            // if route callback returns a ResponseInterface, then use it
                            if ($result instanceof ResponseInterface) {
                                $response = $result;
                            } else {
                                // if route callback returns a string, then append it to the response
                                if (is_string($result) || method_exists($result, '__toString')) {
                                    if ($response->getBody()->isWritable()) {
                                        $response->getBody()->write($result);
                                    }
                                }
                            }
                        } else {
                            if ($val == $action) {
                                throw new NoSuchMethodException(
                                    'Method "' . $action . '" doesn\'t exist in controller "' . get_class($callback) . '"'
                                );
                            }
                        }
                    }
                }
            }

            $output = ob_get_clean();

            if (!empty($output) && $response->getBody()->isWritable()) {
                if ($this->outputBuffering === 'prepend') {
                    // prepend output buffer content
                    $body = new Body(fopen('php://temp', 'r+'));
                    $body->write($output . $response->getBody());
                    $response = $response->withBody($body);
                } elseif ($this->outputBuffering === 'append') {
                    // append output buffer content
                    $response->getBody()->write($output);
                }
            }
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return $response;
    }
}
