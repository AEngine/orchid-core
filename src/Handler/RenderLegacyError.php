<?php

namespace AEngine\Orchid\Handler;

use AEngine\Orchid\Http\Body;
use AEngine\Orchid\Http\Response;
use UnexpectedValueException;

class RenderLegacyError extends AbstractError
{
    /**
     * @param array $error
     *
     * @return Response
     */
    public static function render(array $error)
    {
        $contentType = static::determineContentType(app()->request()->getHeaderLine('Accept'));

        switch ($contentType) {
            case 'application/json':
                $output = static::renderJsonMessage($error);
                break;

            case 'text/html':
                $output = static::renderHtmlMessage($error);
                break;

            default:
                throw new UnexpectedValueException('Cannot render unknown content type ' . $contentType);
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return app()->response()
            ->withStatus(500)
            ->withHeader('Content-type', $contentType)
            ->withBody($body);
    }

    /**
     * Render JSON error
     *
     * @param array $error
     *
     * @return string
     */
    protected static function renderJsonMessage(array $error)
    {
        $json = [
            'message' => 'Application Error',
        ];

        if (app()->isDebug()) {
            $json['error'] = [
                'type' => $error['type'],
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
            ];
        }

        return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Render HTML error page
     *
     * @param array $error
     *
     * @return string
     */
    protected static function renderHtmlMessage(array $error)
    {
        $title = 'Application Error';

        if (app()->isDebug()) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= static::renderHtmlError($error);
        } else {
            $html = '<p>A website error has occurred.</p>';
        }

        $output = sprintf(
            "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
            "<title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana," .
            "sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{" .
            "display:inline-block;width:65px;}</style></head><body><h1>%s</h1>%s</body></html>",
            $title,
            $title,
            $html
        );

        return $output;
    }

    /**
     * Render error as HTML.
     *
     * @param array $error
     *
     * @return string
     */
    protected static function renderHtmlError(array $error)
    {
        $html = '';

        if (($type = $error['type'])) {
            $html .= sprintf('<div><strong>Type:</strong> %s</div>', $type);
        }
        if (($message = $error['message'])) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }
        if (($file = $error['file'])) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }
        if (($line = $error['line'])) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        return $html;
    }
}
