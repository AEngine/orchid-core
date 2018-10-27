<?php

namespace AEngine\Orchid;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use SplDoublyLinkedList;
use SplStack;
use UnexpectedValueException;

trait MiddlewareTrait
{
    /**
     * Middleware call stack
     *
     * @var SplStack
     */
    protected $stack;
    /**
     * Middleware stack lock
     *
     * @var bool
     */
    protected $middlewareLock = false;

    /**
     * Add middleware
     * This method prepends new middleware to the application middleware stack.
     *
     * @param Closure $callable Any callable that accepts three arguments:
     *                          1. A Request object
     *                          2. A Response object
     *                          3. A "next" middleware callable
     *
     * @return $this
     * @throws RuntimeException         If middleware is added while the stack is dequeuing
     * @throws UnexpectedValueException If the middleware doesn't return a Psr\Http\Message\ResponseInterface
     */
    public function addMiddleware($callable)
    {
        if ($this->middlewareLock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }
        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }

        $next = $this->stack->top();
        $this->stack[] = function (ServerRequestInterface $req, ResponseInterface $res) use ($callable, $next) {
            $result = call_user_func($callable, $req, $res, $next);
            if ($result instanceof ResponseInterface === false) {
                throw new UnexpectedValueException(
                    'Middleware must return instance of \Psr\Http\Message\ResponseInterface'
                );
            }

            return $result;
        };

        return $this;
    }

    /**
     * Seed middleware stack with first callable
     *
     * @throws RuntimeException if the stack is seeded more than once
     */
    protected function seedMiddlewareStack()
    {
        if (!is_null($this->stack)) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }

        $this->stack = new SplStack;
        $this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);
        $this->stack[] = $this;
    }

    /**
     * Call middleware stack
     *
     * @param ServerRequestInterface $req A request object
     * @param ResponseInterface      $res A response object
     *
     * @return ResponseInterface
     */
    public function callMiddlewareStack(ServerRequestInterface $req, ResponseInterface $res)
    {
        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }

        /**
         * @var callable $start
         */
        $start = $this->stack->top();
        $this->middlewareLock = true;
        $resp = $start($req, $res);
        $this->middlewareLock = false;

        return $resp;
    }
}
