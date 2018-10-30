<?php

namespace AEngine\Orchid\Provider;

use AEngine\Orchid\Container;
use AEngine\Orchid\Interfaces\ServiceProviderInterface;
use AEngine\Orchid\Path;

class PathProvider implements ServiceProviderInterface
{
    public function register(Container $container) {
        if (!isset($container['path'])) {
            /**
             * Return path helper
             *
             * @param Container $c
             *
             * @return Path
             */
            $container['path'] = function ($c) {
                return new Path($c);
            };
        }
    }
}
