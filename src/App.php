<?php

declare(strict_types=1);

namespace AEngine\Orchid;

use AEngine\Orchid\Exception\FileNotFoundException;
use AEngine\Orchid\Handler\RenderError;
use AEngine\Orchid\Handler\RenderLegacyError;
use AEngine\Orchid\Http\Request;
use AEngine\Orchid\Http\Response;
use BadMethodCallException;
use DirectoryIterator;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * @method Request request();
 * @method Response response();
 * @method Router router();
 */
class App
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * App constructor
     *
     * @param ContainerInterface|array $container
     */
    protected function __construct($container = [])
    {
        if (ob_get_level() === 0) {
            ob_start();
            ob_implicit_flush(0);
        }

        if (PHP_SAPI != 'cli') {
            set_exception_handler(function (Throwable $ex) {
                if (ob_get_level() !== 0) {
                    ob_end_clean();
                }

                $this->respond(RenderError::render($ex));
            });
        }

        if (is_array($container)) {
            $container = new Container($container);
        }
        if (!$container instanceof ContainerInterface) {
            throw new InvalidArgumentException('Expected a ContainerInterface');
        }

        $this->container = $container;

        $self = $this;

        // register auto loader
        spl_autoload_register(function ($class) use ($self) {
            foreach ($self->get('autoload') as $dir) {
                $class_path = $dir . '/' . str_replace(['\\', '_'], '/', $class) . '.php';

                if (file_exists($class_path)) {
                    require_once($class_path);

                    return;
                }
            }
        });
    }

    /**
     * Send the response the client
     *
     * @param ResponseInterface $response
     */
    public function respond(ResponseInterface $response)
    {
        // send response
        if (!headers_sent()) {
            // status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
            // headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // body
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $chunkSize = $this->container->get('settings')['responseChunkSize'];
            $contentLength = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }
            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }

    /**
     * Helper method, which returns true if the provided response must not output a body and false
     * if the response could have a body.
     *
     * @see https://tools.ietf.org/html/rfc7231
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    protected function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }

    /**
     * Return value from internal config
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->container->has($key)) {
            return $this->container->get($key);
        }

        return value($default);
    }

    /**
     * Return App instance
     *
     * @param array $config
     *
     * @return App
     */
    public static function getInstance(array $config = [])
    {
        static $instance;

        if (!$instance) {
            $instance = new App($config);
        }

        return $instance;
    }

    /**
     * Return debug flag
     *
     * @return bool
     */
    public function isDebug()
    {
        return $this->get('debug', true);
    }

    /**
     * Return current app name
     *
     * @return string
     */
    public function getApp()
    {
        return $this->get('app.name', 'public');
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
        if (in_array($name, $this->get('app.list', []))) {
            $this->set('app.name', $name);

            return true;
        }

        throw new RuntimeException('Application "' . $name . '" not found in "app.list"');
    }

    /**
     * Set value for key
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return App
     */
    public function set($key, $value)
    {
        $this->container->set($key, $value);

        return $this;
    }

    /**
     * Load modules from specified folders
     *
     * @param array $folders
     *
     * @return App
     * @throws FileNotFoundException
     * @throws RuntimeException
     */
    public function loadModule(array $folders)
    {
        foreach ($folders as $folder) {
            // add folder to autoload
            $this->add('autoload', $folder);

            foreach (new DirectoryIterator($folder) as $element) {
                if (!$element->isDot() && (
                        $element->isDir() ||
                        $element->isFile() && $element->getExtension() == 'php'
                    )
                ) {
                    $dir = $element->getRealPath();
                    $name = $class = $element->getBasename('.php');

                    if (!is_file($dir)) {
                        $this->path($class, $dir);
                        $dir = $dir . DIRECTORY_SEPARATOR . 'Module' . $class . '.php';

                        // class name with namespace
                        $class = $element->getFilename() . '\\Module' . $class;
                    }

                    if (file_exists($dir)) {
                        require_once($dir);
                    } else {
                        throw new FileNotFoundException('Could not find specified file');
                    }

                    // check exists and parent class
                    if (class_exists($class) && is_subclass_of($class, Module::class)) {
                        // check method initialize exists
                        if (method_exists($class, 'initialize')) {
                            // call initialize method
                            call_user_func([$class, 'initialize'], $this);
                        }
                    } else {
                        throw new RuntimeException(
                            'Class "' . $class . '" not found or is not a subclass of \Aengine\Orchid\Module'
                        );
                    }

                    $this->add('module.list', $name);
                }
            }
        }

        return $this;
    }

    /**
     * Add value for name (not necessary) in array with key
     * <code>
     * $app->add('array', 'bar'); // add index with value 'bar'
     * $app->add('array', 'foo', 'bar'); // add key 'foo' with value 'bar'
     * </code>
     *
     * @param string $key
     * @param array  $element
     *
     * @return App
     */
    public function add($key, ...$element)
    {
        $buf = (array)$this->get($key, []);

        switch (count($element)) {
            case 1:
                $buf[] = $element[0];
                break;
            case 2:
                $buf[$element[0]] = $element[1];
                break;
        }

        $this->set($key, $buf);

        return $this;
    }

    /**
     * Path helper method
     * <code>
     * // set path shortcut
     * $app->path('cache', ORCHID . '/storage/cache');
     *
     * // get path for file
     * $app->path('cache:filename.cache');
     * </code>
     *
     * @param $shortcut
     * @param $path
     *
     * @return App|bool|string
     */
    public function path($shortcut, $path = '')
    {
        if ($shortcut && $path) {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);

            if (!isset($this->paths[$shortcut])) {
                $this->paths[$shortcut] = [];
            }

            array_unshift($this->paths[$shortcut], is_file($path) ? $path : $path . '/');

            return $this;
        } else {
            if (static::isAbsolutePath($shortcut) && file_exists($shortcut)) {
                return $shortcut;
            }

            if (($parts = explode(':', $shortcut, 2)) && count($parts) == 2) {
                if (isset($this->paths[$parts[0]])) {
                    foreach ($this->paths[$parts[0]] as &$shortcut) {
                        if (file_exists($shortcut . $parts[1])) {
                            return $shortcut . $parts[1];
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks is absolute path
     *
     * @param $path
     *
     * @return bool
     */
    public static function isAbsolutePath($path)
    {
        return $path && (
                '/' == $path[0] ||
                '\\' == $path[0] ||
                (3 < mb_strlen($path) && ctype_alpha($path[0]) && $path[1] == ':' &&
                    (
                        '\\' == $path[2] ||
                        '/' == $path[2]
                    )
                )
            );
    }

    /**
     * Return array of loaded modules
     *
     * @return array
     */
    public function getModules()
    {
        return $this->get('module.list', []);
    }

    /**
     * Return secret word
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->get('secret', 'secret');
    }

    /**
     * Return CLI args
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->get('args', []);
    }

    /**
     * Return base dir
     *
     * @return string
     */
    public function getBaseDir()
    {
        return $this->get('base.dir');
    }

    /**
     * Return base host name
     *
     * @return string
     */
    public function getBaseHost()
    {
        return $this->get('base.host');
    }

    /**
     * Return base port num
     *
     * @return int
     */
    public function getBasePort()
    {
        return (int)$this->get('base.port');
    }

    /**
     * Return path list by shortcut
     *
     * @param $shortcut
     *
     * @return array
     */
    public function pathList($shortcut)
    {
        return $this->paths[$shortcut] ?? [];
    }

    /**
     * Convert shortcut to uri
     *
     * @param $path
     *
     * @return bool|string
     */
    public function pathToUrl($path)
    {
        if (($file = $this->path($path)) != false) {
            return '/' . ltrim(str_replace($this->get('base.dir'), '', $file), '/');
        }

        return false;
    }

    /**
     * Run Application
     * This method traverses the application middleware stack and then sends the
     * resultant Response object to the HTTP client.
     *
     * @param bool|false $silent
     *
     * @return ResponseInterface
     */
    public function run($silent = false)
    {
        ini_set('default_mimetype', '');

        $request = $this->request();
        $response = $this->response();

        // dispatch route
        $route = $this->router()->dispatch($request);

        // set output buffering mode
        $route->setOutputBuffering($this->container->get('settings')['outputBuffering']);

        // call route
        $route->callMiddlewareStack($request, $response);

        // if error
        if (($error = error_get_last()) && error_reporting() & $error['type']) {
            if (ob_get_level() !== 0) {
                ob_end_clean();
            }

            $response = RenderLegacyError::render($error);
        }

        if (!$silent) {
            $this->respond($response);
        }

        return $response;
    }

    /**
     * Calling a non-existant method on App checks to see if there's an item
     * in the container that is callable and if so, calls it.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        if ($this->getContainer()->has($method)) {
            $obj = $this->getContainer()->get($method);

            if (is_callable($obj)) {
                return call_user_func_array($obj, $args);
            }

            return $obj;
        }

        throw new BadMethodCallException("Method $method is not a valid method");
    }

    /**
     * Return internal container storage
     *
     * @return Container
     */
    public function &getContainer()
    {
        return $this->container;
    }

    protected function __clone()
    {
    }
}
