<?php

namespace AEngine\Orchid\Annotated;

use AEngine\Orchid\Annotation;
use AEngine\Orchid\AnnotationReader;
use AEngine\Orchid\Interfaces\AnnotatedInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class AnnotatedReflectionClass extends ReflectionClass implements AnnotatedInterface
{
    protected $annotations;
    protected $methods;
    protected $properties;

    /**
     * Return element type
     *
     * @return string
     */
    public function getAnnotatedElementType()
    {
        if ($this->isSubClassOf(Annotation::class)) {
            return 'ANNOTATION';
        }

        return 'CLASS';
    }

    /**
     * Return all annotations
     *
     * @return array
     * @throws ReflectionException
     */
    public function getAnnotations()
    {
        if ($this->annotations == null) {
            $this->annotations = AnnotationReader::parse($this);
        }

        return $this->annotations;
    }

    /**
     * Return annotation by strict name or array of annotations
     *
     * @param string $name
     * @param bool   $strict
     *
     * @return array
     * @throws ReflectionException
     */
    public function getAnnotation($name, $strict = true)
    {
        $annotations = $this->getAnnotations();

        if ($strict) {
            if (isset($annotations[$name])) {
                return $annotations[$name];
            }

            return null;
        }

        foreach ($annotations as $annotation => $obj) {
            if (strpos($annotation, $name) === false) {
                unset($annotations[$annotation]);
            }
        }

        return $annotations;
    }

    /**
     * Check has annotations
     *
     * @param string $annotationClass
     *
     * @return bool
     * @throws ReflectionException
     */
    public function hasAnnotation($annotationClass)
    {
        return !!$this->getAnnotation($annotationClass);
    }

    /**
     * Return class methods
     *
     * @param null $filter
     *
     * @return ReflectionMethod[]
     * @throws ReflectionException
     */
    public function getMethods($filter = null)
    {
        if ($this->methods == null) {
            $methods = parent::getMethods();
            $this->methods = [];
            foreach ($methods as $method) {
                $this->methods[$method->getName()] = new AnnotatedReflectionMethod($this->getName(), $method->getName());
            }
        }

        return $this->methods;
    }

    /**
     * Return class method by name
     *
     * @param string $name
     *
     * @return null|ReflectionMethod
     * @throws ReflectionException
     */
    public function getMethod($name)
    {
        $all = $this->getMethods();

        return (isset($all[$name]) ? $all[$name] : null);
    }

    /**
     * Check has method by name
     *
     * @param string $name
     *
     * @return bool
     * @throws ReflectionException
     */
    public function hasMethod($name)
    {
        return $this->getMethod($name) != null;
    }

    /**
     * Return class properties
     *
     * @param null $filter
     *
     * @return ReflectionProperty[]
     * @throws ReflectionException
     */
    public function getProperties($filter = null)
    {
        if ($this->properties == null) {
            $properties = parent::getProperties();
            $this->properties = [];
            foreach ($properties as $property) {
                $this->properties[$property->getName()] = new AnnotatedReflectionProperty($this->getName(), $property->getName());
            }
        }

        return $this->properties;
    }

    /**
     * Return class properties by name
     *
     * @param string $name
     *
     * @return null|ReflectionProperty
     * @throws ReflectionException
     */
    public function getProperty($name)
    {
        $all = $this->getProperties();

        return (isset($all[$name]) ? $all[$name] : null);
    }

    /**
     * Check has property by name
     *
     * @param string $name
     *
     * @return bool
     * @throws ReflectionException
     */
    public function hasProperty($name)
    {
        return $this->getProperty($name) != null;
    }
}
