<?php

namespace AEngine\Orchid;

use AEngine\Orchid\Exception\ContainerException;
use AEngine\Orchid\Exception\NotFoundException;
use AEngine\Orchid\Provider\MessageProvider;
use AEngine\Orchid\Provider\PathProvider;
use Pimple\Container as PimpleContainer;
use Pimple\Exception\FrozenServiceException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * Default Container
 *
 * @property-read Path $path
 */
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

        // cli mode
        if (PHP_SAPI == 'cli') {
            $values['args'] = array_slice($_SERVER['argv'], 1);
        }

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

        // add Message ServiceProvider
        $messageProvider = new MessageProvider();
        $messageProvider->register($this);

        // add Path ServiceProvider
        $pathProvider = new PathProvider();
        $pathProvider->register($this);
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
     * Add value for name (not necessary) in array with key
     * <code>
     * $app->add('array', 'bar'); // add index with value 'bar'
     * $app->add('array', 'foo', 'bar'); // add key 'foo' with value 'bar'
     * </code>
     *
     * @param string $id
     * @param array  $element
     *
     * @return Container
     */
    public function add($id, ...$element)
    {
        $buf = (array)$this->get($id);

        switch (count($element)) {
            case 1:
                $buf[] = $element[0];
                break;
            case 2:
                $buf[$element[0]] = $element[1];
                break;
        }

        $this->set($id, $buf);

        return $this;
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

    // Alias methods

    /**
     * Return CLI args
     *
     * @return array
     */
    public function getArgs()
    {
        if ($this['args']) {
            return $this['args'];
        }

        return [];
    }

    /**
     * Return debug flag
     *
     * @return bool
     */
    public function isDebug() {
        return $this['debug'];
    }

    /**
     * Return current app name
     *
     * @return string
     */
    public function getApp()
    {
        return $this['app.name'];
    }

    /**
     * Set app name
     *
     * @param $name
     *
     * @return bool
     * @throws RuntimeException
     */
    public function setApp($name)
    {
        if (in_array($name, $this['app.list'])) {
            $this['app.name'] = $name;

            return true;
        }

        throw new RuntimeException('Application "' . $name . '" not found in "app.list"');
    }

    /**
     * Return secret word
     *
     * @return string
     */
    public function getSecret()
    {
        return $this['secret'];
    }

    /**
     * Return base dir
     *
     * @return string
     */
    public function getBaseDir()
    {
        return $this['base.dir'];
    }

    /**
     * Return base host name
     *
     * @return string
     */
    public function getBaseHost()
    {
        return $this['base.host'];
    }

    /**
     * Return base port num
     *
     * @return int
     */
    public function getBasePort()
    {
        return (int)$this['base.port'];
    }

    /**
     * Return array of loaded modules
     *
     * @return array
     */
    public function getModules()
    {
        return $this['module.list'];
    }

    /**
     * Return settings value by key or array of settings
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getSettings($id = null)
    {
        if (!is_null($id)) {
            if (isset($this['settings'][$id])) {
                return $this['settings'][$id];
            }

            throw new RuntimeException('Key "' . $id . '" not found in "settings"');
        }

        return $this['settings'];
    }

    /**
     * Set settings by key
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function setSettings($id, $value)
    {
        $this['settings'][$id] = $value;

        return true;
    }

    // Magic methods

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }
}
