<?php

namespace AEngine\Orchid\Handler;

abstract class AbstractError
{
    /**
     * Known handled content types
     *
     * @var array
     */
    protected static $knownContentTypes = [
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
    ];

    /**
     * Read the accept header and determine which content type we know about
     * is wanted.
     *
     * @param string $acceptHeader Accept header from request
     *
     * @return string
     */
    protected static function determineContentType($acceptHeader)
    {
        $list = explode(',', $acceptHeader);

        foreach ($list as $type) {
            if (in_array($type, static::$knownContentTypes)) {
                return $type;
            }
        }

        return 'text/html';
    }
}
