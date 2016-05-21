<?php

namespace Orchid;

use Closure;
use RuntimeException;

class Router {
	/**
	 * Array of route rules
	 *
	 * @var array
	 */
	protected $routes = [];

	/**
	 * Bind GET request to route
	 *
	 * @param string  $pattern
	 * @param Closure $callable
	 * @param int     $priority
	 *
	 * @return $this
	 */
	public function get($pattern, $callable, $priority = 0) {
		return $this->bind($pattern, $callable, Request::METHOD_GET, $priority);
	}

	/**
	 * Bind POST request to route
	 *
	 * @param string  $pattern
	 * @param Closure $callable
	 * @param int     $priority
	 *
	 * @return $this
	 */
	public function post($pattern, $callable, $priority = 0) {
		return $this->bind($pattern, $callable, Request::METHOD_POST, $priority);
	}

	/**
	 * Bind request to route
	 *
	 * @param string  $pattern
	 * @param Closure $callable
	 * @param string  $method
	 * @param int     $priority
	 *
	 * @return $this
	 */
	public function bind($pattern, $callable, $method = Request::METHOD_GET, $priority = 0) {
		$this->routes[] = [
			"method"   => strtoupper($method),
			"pattern"  => $pattern,
			"callback" => $callable,
			"priority" => $priority,
		];

		return $this;
	}

	/**
	 * Dispatch route
	 *
	 * @param Request $request
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function dispatch(Request $request) {
		$method = $request->getMethod();
		$pathname = $request->getPathname();
		$uri = $request->getUri();

		$found = null;
		$params = [];

		if ($this->routes) {
			usort($this->routes, [$this, "compare"]);

			foreach ($this->routes as $route) {
				if ($route["method"] == $method) {
					if ($route["pattern"] === $pathname) {
						$found = $route["callback"];
						break;
					}

					/* #\.html$#  */
					if (substr($route["pattern"], 0, 1) == "#" && substr($route["pattern"], -1) == "#") {
						if (preg_match($route["pattern"], $pathname, $match)) {
							$params[":capture"] = array_slice($match, 1);
							$found = $route["callback"];
							break;
						}
					}

					/* /example/* */
					if (strpos($route["pattern"], "*") !== false) {
						$pattern = "#^" . str_replace("\\*", "(.*)", preg_quote($route["pattern"], "#")) . "#";
						if (preg_match($pattern, $pathname, $match)) {
							$params[":arg"] = array_slice($match, 1);
							$found = $route["callback"];
							break;
						}
					}

					/* /example/:id */
					if (strpos($route["pattern"], ":") !== false) {
						$parts = explode("/", $route["pattern"]);
						array_shift($parts);

						if (count($uri) == count($parts)) {
							$matched = true;
							foreach ($parts as $index => $part) {
								if (":" === substr($part, 0, 1)) {
									$params[substr($part, 1)] = $uri[$index];
									continue;
								}
								if ($uri[$index] != $parts[$index]) {
									$matched = false;
									break;
								}
							}
							if ($matched) {
								$found = $route["callback"];
								break;
							}
						}
					}
				}

			}
		}

		if ($found) {
			return call_user_func_array($found, $params);
		}

		throw new RuntimeException("Failed to find and execute the function");
	}

	/**
	 * @param $a
	 * @param $b
	 *
	 * @return mixed
	 */
	protected function compare($a, $b) {
		return $b["priority"] - $a["priority"];
	}
}