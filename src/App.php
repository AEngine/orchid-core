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
 * @method mixed get($id);
 * @method Container set($id, $value);
 * @method Container add($id, ...$element);
 * @method Path path($shortcut, $path = '');
 * @method array getArgs();
 * @method boolean isDebug();
 * @method string getApp();
 * @method boolean setApp($name);
 * @method string getSecret();
 * @method string getBaseDir();
 * @method string getBaseHost();
 * @method integer getBasePort();
 * @method array getModules();
 * @method mixed getSettings($id=null);
 * @method boolean setSettings($id, $value);
 *
 * @property-read Path $path
 */
class App
{
    /**
     * @var Container
     */
    protected $container;

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
            $instance = new static($config);
        }

        return $instance;
    }

    /**
     * @return App
     */
    protected function __clone()
    {
        return static::getInstance();
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
        $obj = null;

        switch (true) {
            case $this->container->has($method):
                $obj = $this->container->get($method);
                break;
            case method_exists($this->container, $method):
                $obj = [$this->container, $method];
                break;
        }

        if ($obj) {
            if (is_callable($obj)) {
                return call_user_func_array($obj, $args);
            }

            return $obj;
        }

        throw new BadMethodCallException("Method $method is not a valid method");
    }

    /**
     * Calling a non-existant field on App
     *
     * @param string $field
     *
     * @return mixed
     */
    public function __get($field) {
        if ($this->container->has($field)) {
            return $this->container->get($field);
        }

        throw new BadMethodCallException("Method $field is not a valid method");
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
        $response = $route->callMiddlewareStack($request, $response);

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
}
