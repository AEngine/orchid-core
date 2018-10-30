<?php

namespace AEngine\Orchid\Provider;

use AEngine\Orchid\Container;
use AEngine\Orchid\Http\Environment;
use AEngine\Orchid\Http\Headers;
use AEngine\Orchid\Http\Request;
use AEngine\Orchid\Http\Response;
use AEngine\Orchid\Interfaces\ServiceProviderInterface;
use AEngine\Orchid\Router;

class MessageProvider implements ServiceProviderInterface
{
    public function register(Container $container) {
        if (!isset($container['environment'])) {
            /**
             * This service MUST return array from Environment
             *
             * @return array
             */
            $container['environment'] = function () {
                return Environment::mock($_SERVER);
            };
        }

        if (!isset($container['request'])) {
            /**
             * PSR-7 Request object
             *
             * @param Container $c
             *
             * @return Request
             */
            $container['request'] = function ($c) {
                return Request::createFromGlobals($c->get('environment'));
            };
        }

        if (!isset($container['headers'])) {
            /**
             * PSR-7 Request object
             *
             * @return Headers
             */
            $container['headers'] = function () {
                return new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
            };
        }

        if (!isset($container['response'])) {
            /**
             * PSR-7 Response object
             *
             * @param Container $c
             *
             * @return Response
             */
            $container['response'] = function ($c) {
                $response = new Response(200, $c->get('headers'));

                return $response->withProtocolVersion($c->get('settings')['httpVersion']);
            };
        }

        if (!isset($container['router'])) {
            /**
             * @return Router
             */
            $container['router'] = function () {
                return new Router();
            };
        }
    }
}
