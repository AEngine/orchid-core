<?php

namespace AEngine\Orchid\Annotations\Interfaces;

use ReflectionException;

interface AnnotatedInterface
{
    /**
     * Return element type
     *
     * @return string
     */
    public function getAnnotatedElementType();

    /**
     * Return all annotations
     *
     * @return array
     * @throws ReflectionException
     */
    public function getAnnotations();

    /**
     * Return annotation by strict name or array of annotations
     *
     * @param string $name
     * @param bool   $strict
     *
     * @return array
     * @throws ReflectionException
     */
    public function getAnnotation($name, $strict = true);

    /**
     * Check has annotations
     *
     * @param string $annotationClass
     *
     * @return bool
     * @throws ReflectionException
     */
    public function hasAnnotation($annotationClass);

    /**
     * Return element name
     *
     * @return mixed
     */
    public function getName();

    /**
     * Return element doc comment
     *
     * @return mixed
     */
    public function getDocComment();
}
