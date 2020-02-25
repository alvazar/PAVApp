<?php
namespace PAVApp\MVC;

use PAVApp\Core\ResultInterface;

interface ModelInterface
{
    public function apply(array $params = []): ResultInterface;
}