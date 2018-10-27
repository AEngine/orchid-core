<?php

namespace AEngine\Orchid\Annotation;

use AEngine\Orchid\Annotation;

class Target extends Annotation
{
    public $type;

    public function __construct(string $type)
    {
        $this->set('type', strtoupper($type));
    }
}
