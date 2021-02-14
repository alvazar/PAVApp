<?php
namespace PAVApp\MVC;

use PAVApp\Core\ResultInterface;
use PAVApp\Core\RequestInterface;

interface RouteInterface
{
    public static function run($cb, array $params = []): ResultInterface;
    public static function get(string $queryTrigger, $cb, array $params = []): void;
    public static function post(string $queryTrigger, $cb, array $params = []): void;
    public static function start(RequestInterface $Req): void;
}