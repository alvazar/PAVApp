<?php
namespace PAVApp\MVC;

use PAVApp\Core\ResultInterface;

abstract class ControllerAbstract implements ControllerInterface
{
    protected $params;
    protected $Model;
    protected $View;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->Model = $this->getModel();
        $this->View = $this->getView();
    }

    public function actionDefault(): ResultInterface
    {
        $params = $this->getParams();
        $Result = null;
        if (is_object($this->Model)) {
            $Result = $this->Model->apply($params);
            $params = $Result->getData(); // set model result as view component params
        }
        if (is_object($this->View)) {
            $Result = $this->View->generate($params);
        }
        return $Result;
    }

    protected function getModel(): ?ModelInterface
    {
        return null;
    }

    protected function getView(): ?ViewInterface
    {
        return null;
    }
    
    protected function getParams(): array
    {
        return [];
    }
}