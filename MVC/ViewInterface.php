<?php
namespace PAVApp\MVC;

use PAVApp\Core\ResultInterface;

interface ViewInterface
{
    public function generate(array $data = []): ResultInterface;
}