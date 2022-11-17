<?php
namespace PAVApp\MVC;

use PAVApp\Core\Template;
use PAVApp\Core\ResultInterface;
use PAVApp\Core\Result;

abstract class ViewAbstract implements ViewInterface
{
    protected $Template;
    protected $Result;

    public function __construct()
    {
        $this->Template = new Template();
        $this->Result = new Result();
    }

    abstract public function generate(array $data = []): ResultInterface;
}
