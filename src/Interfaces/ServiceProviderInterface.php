<?php

namespace AEngine\Orchid\Interfaces;

use AEngine\Orchid\Container;

interface ServiceProviderInterface
{
    public function register(Container $container);
}
