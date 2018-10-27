<?php

namespace AEngine\Orchid;

use AEngine\Orchid\Exception\ContainerException;
use AEngine\Orchid\Exception\NotFoundException;
use AEngine\Orchid\Http\Environment;
use AEngine\Orchid\Http\Headers;
use AEngine\Orchid\Http\Request;
use AEngine\Orchid\Http\Response;
use Pimple\Container as PimpleContainer;
use Pimple\Exception\FrozenServiceException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container extends PimpleContainer implements ContainerInterface
{
    /**
     * Default settings
     *
     * @var array
     */
    protected static $defaultConfig = [
        'app.name' => 'public',
        'app.list' => [],
        'args' => [],
        'autoload' => [],
        'base.dir' => '',
        'base.host' => '',
        'base.port' => 0,
        'debug' => true,
        'module.list' => [],
        'secret' => '',
        'settings' => [
            'httpVersion' => '1.1',
            'responseChunkSize' => 4096,
            'outputBuffering' => 'append',
        ],
    ];

    /**
     * Create new container
     *
     * @param array $values The parameters or objects
     */
    public function __construct(array $values = [])
    {
        $values = array_replace_recursive(static::$defaultConfig, $values);

        // set base dir
        if (!$values['base.dir']) {
            if (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $values['base.dir'] = $_SERVER['DOCUMENT_ROOT'];
            } else if (defined('ORCHID')) {
                $values['base.dir'] = ORCHID;
            }
        }

        // set base host
        if (!$values['base.host'] && isset($_SERVER['HTTP_HOST'])) {
            $values['base.host'] = $_SERVER['HTTP_HOST'];
        }

        // set base port
        if (!$values['base.port'] && isset($_SERVER['SERVER_PORT'])) {
            $values['base.port'] = $_SERVER['SERVER_PORT'];
        }

        // cli mode
        if (PHP_SAPI == 'cli') {
            $values['args'] = array_slice($_SERVER['argv'], 1);
        }

        // todo remove in next version
        foreach ($values as $key => $value) {
            if (in_array($key, ['base_dir', 'base_host', 'base_port'])) {
                if ($values['debug']) {
                    user_error(sprintf('Config key with name %s is deprecated, see default config in App class.', $key));
                }

                $values[str_replace('_', '.', $key)] = $value;
                unset($values[$key]);
            }
        }

        parent::__construct($values);

        if (!isset($this['environment'])) {
            /**
             * This service MUST return array from Environment
             *
             * @return array
             */
            $this['environment'] = function () {
                return Environment::mock($_SERVER);
            };
        }

        if (!isset($this['request'])) {
            /**
             * PSR-7 Request object
             *
             * @param Container $container
             *
             * @return Request
             */
            $this['request'] = function ($container) {
                return Request::createFromGlobals($container->get('environment'));
            };
        }

        if (!isset($this['headers'])) {
            /**
             * PSR-7 Request object
             *
             * @return Headers
             */
            $this['headers'] = function () {
                return new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
            };
        }

        if (!isset($this['response'])) {
            /**
             * PSR-7 Response object
             *
             * @param Container $container
             *
             * @return Response
             */
            $this['response'] = function ($container) {
                $response = new Response(200, $container->get('headers'));

                return $response->withProtocolVersion($container->get('settings')['httpVersion']);
            };
        }

        if (!isset($this['router'])) {
            /**
             * @return Router
             */
            $this['router'] = function () {
                return new Router();
            };
        }
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     */
    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            throw new NotFoundException(sprintf('Identifier "%s" is not defined.', $id));
        }
        try {
            return $this->offsetGet($id);
        } catch (\InvalidArgumentException $exception) {
            $trace = $exception->getTrace()[0];

            if ($trace['class'] === PimpleContainer::class && $trace['function'] === 'offsetGet') {
                throw new ContainerException(
                    sprintf('Container error while retrieving "%s"', $id),
                    null,
                    $exception
                );
            } else {
                throw $exception;
            }
        }

    }

    /**
     * Set an entry in container
     *
     * @param string $id    The unique identifier for the parameter or object
     * @param mixed  $value The value of the parameter or a closure to define an object
     *
     * @return Container
     * @throws FrozenServiceException Prevent override of a frozen service
     */
    public function set($id, $value)
    {
        $this->offsetSet($id, $value);

        return $this;
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    // Magic methods

    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }
}
