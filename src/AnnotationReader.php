<?php

namespace AEngine\Orchid\Annotations;

use AEngine\Orchid\Annotations\Annotated\AnnotatedReflectionClass;
use AEngine\Orchid\Annotations\Annotation\Target;
use AEngine\Orchid\Annotations\Helper\TokenParser;
use AEngine\Orchid\Annotations\Interfaces\AnnotatedInterface;
use AEngine\Orchid\Annotations\Interfaces\AnnotationInterface;
use ReflectionException;
use RuntimeException;
use SplFileObject;

class AnnotationReader
{
    // tags used for generating PHP Docs (http://www.phpdoc.org/)
    protected static $tags = [
        '@abstract', '@access', '@author',
        '@copyright', '@deprecated', '@deprec', '@example', '@exception',
        '@global', '@ignore', '@internal', '@param', '@return', '@link',
        '@name', '@magic', '@package', '@see', '@since', '@static',
        '@staticvar', '@subpackage', '@throws', '@todo', '@var', '@version',
    ];

    /**
     * Annotation parser
     *
     * @param AnnotatedInterface $element
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function parse(AnnotatedInterface $element)
    {
        $annotations = [];
        $comment = $element->getDocComment();

        // parsing is only required if comment is present
        if (strlen(trim($comment)) > 0) {
            $matches = [];

            // find all annotations (this may include PHP Doc tags)
            preg_match_all('/@(.*)[\n|\*]/', $comment, $matches);

            foreach ($matches[1] as $match) {
                $match = trim($match);
                $args = [];
                $props = [];

                // annotation with parameters
                if (strpos($match, '(') > 0) {
                    $parts = [];

                    preg_match_all('/^(.*?)\((.*?)\)/', $match, $parts);

                    $name = $parts[1][0];

                    // don't process any further if annotation is a PHP Doc tag
                    if (in_array('@' . $name, static::$tags)) continue;


                    // break parts up into individual args/props
                    $t = [];
                    $tmp = '';
                    $arr = false;
                    for ($i = strlen($parts[2][0]) - 1; $i >= 0; $i--) {
                        $chr = $parts[2][0][$i];
                        if ($chr == '}') {
                            $arr = true;
                        } else if ($chr == '{') {
                            $arr = false;
                        } else if ($chr == ',' && !$arr) {
                            $t[] = strrev($tmp);
                            $tmp = '';
                        } else {
                            $tmp .= $chr;
                        }
                    }
                    $t[] = strrev($tmp);
                    $t = array_reverse($t);

                    // assign args/props accordingly
                    foreach ($t as $a) {
                        switch (true) {
                            case strlen(trim($a)) === 0:
                                continue;

                            // named properties
                            case strpos($a, '=') > 0:
                                $kv = explode('=', $a);
                                $props[trim($kv[0])] = static::value($kv[1]);
                                break;

                            // constructor arguments
                            default:
                                $args[] = static::value($a);
                                break;
                        }
                    }
                } else {
                    $name = explode(' ', trim($match), 2)[0];

                    // Don't process any further if annotation is a PHP Doc tag
                    if (in_array('@' . $name, static::$tags)) continue;
                }

                $result = static::create($element, $name, $args, $props);

                if ($result != null) {
                    $annotations[$name] = $result;
                }
            }
        }

        return $annotations;
    }

    /**
     * Value reader
     *
     * @param $val
     *
     * @return array|bool|string
     */
    protected static function value($val)
    {
        $val = trim($val);

        switch (true) {
            // array
            case strpos($val, ',') > 0:
                $val = explode(',', $val);

                foreach ($val as $idx => $tmp) {
                    $val[$idx] = static::value($tmp);
                }
                break;

            // string
            case preg_match('/^([\'"]).*([\'"])$/', $val):
                $val = substr($val, 1);
                $val = substr($val, 0, strlen($val) - 1);
                break;

            // evaluable (int, boolean, constant, etc.)
            default:
                eval('$val = ' . $val . ';');
                break;
        }

        return $val;
    }

    /**
     * Create annotation object
     *
     * @param AnnotatedInterface $element
     * @param string             $name
     * @param array              $args
     * @param array              $props
     *
     * @return null|object
     * @throws \ReflectionException
     */
    protected static function create(AnnotatedInterface $element, $name, array $args = [], array $props = [])
    {
        // ensure that the class exists
        if (!class_exists($name)) {
            if (in_array($element->getAnnotatedElementType(), ['CONSTRUCTOR', 'METHOD', 'PROPERTY'])) {
                $declaringClass = $element->getDeclaringClass();
            } else {
                $declaringClass = $element;
            }
            $name = explode('\\', $name, 2);
            $name = end($name);

            foreach (static::parseClass($declaringClass) as $lowercaseName => $namespace) {
                switch (true) {
                    case strtolower($name) === $lowercaseName:
                        $name = $namespace;
                        break;
                    case class_exists($namespace . '\\' . $name):
                        $name = $namespace . '\\' . $name;
                        break;
                }
            }
        }

        $class = new AnnotatedReflectionClass($name);

        // ensure that class is a class implement AnnotationInterface
        if (isset($class) && $class->isSubClassOf(AnnotationInterface::class)) {
            // validate annotation target
            static::validate($element, $class);

            // instantiate annotation with constructor arguments
            $result = $class->newInstanceArgs($args);

            if ($props) {
                $result->replace($props);
            }

            return $result;
        }

        return null;
    }

    /**
     * @param $class
     *
     * @return array
     */
    protected static function parseClass($class)
    {
        if (false === $filename = $class->getFileName()) {
            return [];
        }

        $content = static::getFileContent($filename, $class->getStartLine());

        if (null === $content) {
            return [];
        }

        $namespace = preg_quote($class->getNamespaceName());
        $content = preg_replace('/^.*?(\bnamespace\s+' . $namespace . '\s*[;{].*)$/s', '\\1', $content);
        $tokenizer = new TokenParser('<?php ' . $content);

        $statements = array_merge(
            ['__NAMESPACE__' => $class->getNamespaceName()],
            $tokenizer->parseUseStatements($class->getNamespaceName())
        );

        return $statements;
    }

    /**
     * @param string  $filename
     * @param integer $lineNumber
     *
     * @return null|string
     */
    protected static function getFileContent($filename, $lineNumber)
    {
        if (!is_file($filename)) {
            return null;
        }

        $content = '';
        $lineCnt = 0;

        $file = new SplFileObject($filename);

        while (!$file->eof()) {
            if ($lineCnt++ == $lineNumber) {
                break;
            }

            $content .= $file->fgets();
        }

        return $content;
    }

    /**
     * Validate correct annotation use
     *
     * @param AnnotatedInterface       $element
     * @param AnnotatedReflectionClass $class
     *
     * @throws ReflectionException
     */
    protected static function validate(AnnotatedInterface $element, AnnotatedReflectionClass $class)
    {
        if ($element->getName() == Target::class) {
            return;
        }

        if ($class->hasAnnotation(Target::class)) {
            /**
             * @var Target $target
             */
            $target = $class->getAnnotation(Target::class);
            $type = $target->get('type');

            if (
                $type != null &&
                (
                    is_string($type) && $element->getAnnotatedElementType() == $type ||
                    is_array($type) && in_array($element->getAnnotatedElementType(), $type)
                )
            ) {
                return;
            }

            throw new RuntimeException('Invalid annotation "' . $class->getName() . '" for "' . $element->getName() . '"');
        }
    }
}
