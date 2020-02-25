<?php
namespace PAVApp\MVC;

use PAVApp\Core\ResultInterface;

interface RouteInterface
{
    public static function run(array $request): ResultInterface;
}