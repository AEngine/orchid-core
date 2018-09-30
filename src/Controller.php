<?php

namespace AEngine\Orchid;

abstract class Controller
{
    /**
     * If set to False stops controller execution,
     * but before the method will work in any case
     *
     * @var bool
     */
    public $execute = true;

    /*
     * public function before($request, $response) : ResponseInterface
     * {
     *     return $response;
     * }
     *
     * public function index($request, $response) : ResponseInterface
     * {
     *     return $response;
     * }
     *
     * public function after($request, $response) : ResponseInterface
     * {
     *     return $response;
     * }
     */
}
