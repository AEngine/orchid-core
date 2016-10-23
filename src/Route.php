<?php

namespace AEngine\Orchid;

use AEngine\Orchid\Exception\NoSuchMethodException;
use AEngine\Orchid\Interfaces\RouteInterface;
use Closure;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UnexpectedValueException;

/**
 * Route
 */
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
     *
     * One of: false, 'prepend' or 'append'
     *
     * @var boolean|string
     */
    protected $outputBuffering = 'prepend';

    /**
     * Create new route
     *
     * @param string|string[] $methods    The route HTTP methods
     * @param string          $pattern    The route pattern
     * @param array|Closure   $callable   The route callable
     * @param int             $priority   The route priority
     * @param RouteGroup[]    $groups     The parent route groups
     * @param int             $identifier The route identifier
     */
    public function __construct($methods, $pattern, $callable, $priority = 0, $groups = [], $identifier = 0)
    {
        $this->methods = is_string($methods) ? [$methods] : $methods;
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->groups = $groups;
        $this->priority = $priority;
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
     *
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
     *
     * This method invokes the route object's callable. If middleware is
     * registered for the route, each callable middleware is invoked in
     * the order specified.
     *
     * @param ServerRequestInterface $request  The current Request object
     * @param ResponseInterface      $response The current Response object
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Exception  if the route callable throws an exception
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            ob_start();

            if (is_object($this->callable)) {
                $newResponse = call_user_func_array($this->callable, [$request, $response, $this->getArgument()]);
            } else {
                if (is_array($this->callable)) {
                    $controller = !empty($this->callable[0]) ? $this->callable[0] : '';
                    $action = !empty($this->callable[1]) ? $this->callable[1] : 'index';
                } else {
                    $controller = $this->callable;
                    $action = 'index';
                }

                if ($controller && class_exists($controller)) {
                    $newResponse = null;
                    $obj = new $controller;

                    if ($obj && is_a($obj, Controller::class)) {
                        if (method_exists($obj, "before")) {
                            call_user_func_array([$obj, "before"], [$request, $response]);
                        }

                        if ($obj->execute) {
                            if (method_exists($obj, $action)) {
                                $newResponse = call_user_func_array(
                                    [$obj, $action],
                                    [$request, $response, $this->getArgument()]
                                );
                            } else {
                                throw new NoSuchMethodException(
                                    'Method "' . $action . '" doesn\'t exist in controller "' . get_class($obj) . '"'
                                );
                            }
                        }

                        if ($obj->execute) {
                            if (method_exists($obj, "after")) {
                                call_user_func_array([$obj, "after"], [$request, $response]);
                            }
                        }
                    } else {
                        throw new UnexpectedValueException(
                            'Controller must return extends of \AEngine\Orchid\Controller'
                        );
                    }
                } else {
                    throw new UnexpectedValueException(
                        'Route callable must be defined as array or string or object'
                    );
                }
            }

            $output = ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }

        if ($newResponse instanceof ResponseInterface) {
            // if route callback returns a ResponseInterface, then use it
            $response = $newResponse;
        } else {
            if (is_string($newResponse) || method_exists($newResponse, '__toString')) {
                // if route callback returns a string, then append it to the response
                if ($response->getBody()->isWritable()) {
                    $response->getBody()->write($newResponse);
                }
            }
        }

        if (!empty($output) && $response->getBody()->isWritable()) {
            if ($this->outputBuffering === 'prepend') {
                // prepend output buffer content
                $body = new Http\Body(fopen('php://temp', 'r+'));
                $body->write($output . $response->getBody());
                $response = $response->withBody($body);
            } elseif ($this->outputBuffering === 'append') {
                // append output buffer content
                $response->getBody()->write($output);
            }
        }

        return $response;
    }
}
