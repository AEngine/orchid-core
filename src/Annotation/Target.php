<?php

namespace AEngine\Orchid\Annotations\Annotation;

use AEngine\Orchid\Annotations\Annotation;

class Target extends Annotation
{
    public $type;

    public function __construct(string $type)
    {
        $this->set('type', strtoupper($type));
    }
}
