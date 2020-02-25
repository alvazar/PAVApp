<?php
namespace PAVApp\MVC;

use PAVApp\Core\ResultInterface;
use PAVApp\Core\Result;

abstract class ModelAbstract implements ModelInterface
{
    protected $Result;

    public function __construct()
    {
        $this->Result = new Result();
    }

    abstract public function apply(array $params = []): ResultInterface;
}