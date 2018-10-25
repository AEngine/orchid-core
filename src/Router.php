<?php

namespace AEngine\Orchid;

use Closure;
use AEngine\Orchid\Interfaces\RouteGroupInterface;
use AEngine\Orchid\Interfaces\RouteInterface;
use AEngine\Orchid\Interfaces\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;
use RuntimeException;

class Router implements RouterInterface
{
    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Route counter incrementer
     *
     * @var int
     */
    protected $routeCounter = 0;

    /**
     * Route groups
     *
     * @var RouteGroup[]
     */
    protected $routeGroups = [];

    /**
     * Add GET route
     *
     * @param  string               $pattern The route URI pattern
     * @param  string|array|Closure $callable The route callback routine
     * @param  int                  $priority The route priority
     *
     * @return RouteInterface
     */
    public function get($pattern, $callable, $priority = 0)
    {
        return $this->map(['GET'], $pattern, $callable, $priority);
    }

    /**
     * Add POST route
     *
     * @param  string               $pattern The route URI pattern
     * @param  string|array|Closure $callable The route callback routine
     * @param  int                  $priority The route priority
     *
     * @return RouteInterface
     */
    public function post($pattern, $callable, $priority = 0)
    {
        return $this->map(['POST'], $pattern, $callable, $priority);
    }

    /**
     * Add PUT route
     *
     * @param  string               $pattern The route URI pattern
     * @param  string|array|Closure $callable The route callback routine
     * @param  int                  $priority The route priority
     *
     * @return RouteInterface
     */
    public function put($pattern, $callable, $priority = 0)
    {
        return $this->map(['PUT'], $pattern, $callable, $priority);
    }

    /**
     * Add PATCH route
     *
     * @param  string               $pattern The route URI pattern
     * @param  string|array|Closure $callable The route callback routine
     * @param  int                  $priority The route priority
     *
     * @return RouteInterface
     */
    public function patch($pattern, $callable, $priority = 0)
    {
        return $this->map(['PATCH'], $pattern, $callable, $priority);
    }

    /**
     * Add DELETE route
     *
     * @param  string               $pattern The route URI pattern
     * @param  string|array|Closure $callable The route callback routine
     * @param  int                  $priority The route priority
     *
     * @return RouteInterface
     */
    public function delete($pattern, $callable, $priority = 0)
    {
        return $this->map(['DELETE'], $pattern, $callable, $priority);
    }

    /**
     * Add OPTIONS route
     *
     * @param  string               $pattern The route URI pattern
     * @param  string|array|Closure $callable The route callback routine
     * @param  int                  $priority The route priority
     *
     * @return RouteInterface
     */
    public function options($pattern, $callable, $priority = 0)
    {
        return $this->map(['OPTIONS'], $pattern, $callable, $priority);
    }

    /**
     * Add HEAD route
     *
     * @param  string               $pattern The route URI pattern
     * @param  string|array|Closure $callable The route callback routine
     * @param  int                  $priority The route priority
     *
     * @return RouteInterface
     */
    public function head($pattern, $callable, $priority = 0)
    {
        return $this->map(['HEAD'], $pattern, $callable, $priority);
    }

    /**
     * Add route for any HTTP method
     *
     * @param  string               $pattern The route URI pattern
     * @param  string|array|Closure $callable The route callback routine
     * @param  int                  $priority The route priority
     *
     * @return RouteInterface
     */
    public function any($pattern, $callable, $priority = 0)
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $pattern, $callable, $priority);
    }

    /**
     * @param array                $methods
     * @param string               $pattern
     * @param string|array|Closure $callable
     * @param int                  $priority
     *
     * @return RouteInterface
     */
    public function map(array $methods, $pattern, $callable, $priority = 0): RouteInterface
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // Prepend parent group pattern(s)
        if ($this->routeGroups) {
            $pattern = $this->processGroups() . $pattern;
        }

        // According to RFC methods are defined in uppercase (See RFC 7231)
        $methods = array_map('strtoupper', $methods);

        $route = new Route($methods, $pattern, $callable, $priority, $this->routeGroups, $this->routeCounter);

        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCounter++;

        return $route;
    }

    /**
     * Add a route that sends an HTTP redirect
     *
     * @param string              $from
     * @param string|UriInterface $to
     * @param int                 $status
     *
     * @return RouteInterface
     */
    public function redirect($from, $to, $status = 302)
    {
        $handler = function ($request, ResponseInterface $response) use ($to, $status) {
            return $response->withHeader('Location', (string)$to)->withStatus($status);
        };

        return $this->get($from, $handler);
    }

    /**
     * Get route objects
     *
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Add a route group to the array
     *
     * @param string   $pattern
     * @param callable $callable
     *
     * @return RouteGroupInterface
     */
    public function group($pattern, $callable)
    {
        $group = new RouteGroup($pattern, $callable);

        array_push($this->routeGroups, $group);
        $group();
        array_pop($this->routeGroups);

        return $group;
    }

    /**
     * Process route groups
     *
     * @return string A group pattern to prefix routes with
     */
    protected function processGroups()
    {
        $pattern = '';
        foreach ($this->routeGroups as $group) {
            $pattern .= $group->getPattern();
        }

        return $pattern;
    }

    /**
     * Dispatch router for HTTP request
     *
     * @param  ServerRequestInterface $request The current HTTP request object
     *
     * @return RouteInterface
     * @throws RuntimeException
     */
    public function dispatch(ServerRequestInterface $request)
    {
        if (!$this->routes) {
            throw new RuntimeException('Route list is empty');
        }

        $method   = $request->getMethod();
        $pathname = '/' . ltrim($request->getUri()->getPath(), '/');
        $found    = null; // current route
        $params   = [];

        usort($this->routes, [$this, 'compare']);

        foreach ($this->routes as $route) {
            if (in_array($method, $route->getMethods())) {
                if ($route->getPattern() === $pathname) {
                    $found = $route;
                    break;
                }

                /* #\.html$#  */
                if (substr($route->getPattern(), 0, 1) == '#' && substr($route->getPattern(), -1) == '#') {
                    if (preg_match($route->getPattern(), $pathname, $match)) {
                        $params[':capture'] = array_slice($match, 1);
                        $found              = $route;
                        break;
                    }
                }

                /* /example/* */
                if (strpos($route->getPattern(), '*') !== false) {
                    $pattern = '#^' . str_replace('\\*', '(.*)', preg_quote($route->getPattern(), '#')) . '#';
                    if (preg_match($pattern, $pathname, $match)) {
                        $params[':arg'] = array_slice($match, 1);
                        $found          = $route;
                        break;
                    }
                }

                /* /example/:id */
                if (strpos($route->getPattern(), ':') !== false) {
                    $uri = explode('/', $pathname);
                    array_shift($uri);
                    $parts = explode('/', $route->getPattern());
                    array_shift($parts);

                    if (count($uri) == count($parts)) {
                        $matched = true;
                        foreach ($parts as $index => $part) {
                            if (':' === substr($part, 0, 1)) {
                                $params[substr($part, 1)] = $uri[$index];
                                continue;
                            }
                            if ($uri[$index] != $parts[$index]) {
                                $matched = false;
                                break;
                            }
                        }
                        if ($matched) {
                            $found = $route;
                            break;
                        }
                    }
                }
            }
        }

        // if not found route for url
        if (!$found) {
            throw new RuntimeException('Failed to find and execute the function');
        }

        return $found->setArguments($params);
    }

    /**
     * @param RouteInterface $a
     * @param RouteInterface $b
     *
     * @return mixed
     */
    protected function compare($a, $b)
    {
        return $b->getPriority() - $a->getPriority();
    }
}
