<?php
namespace PAVApp\MVC;

use PAVApp\Core\ResultInterface;

abstract class ControllerAbstract implements ControllerInterface
{
    protected $params;
    protected $model;
    protected $view;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->model = $this->getModel();
        $this->view = $this->getView();
    }

    public function actionDefault(): ResultInterface
    {
        $params = $this->getParams();
        $result = null;

        if (is_object($this->model)) {
            $result = $this->model->apply($params);
            $params = $result->getData(); // set model result as view component params
        }

        if (is_object($this->view)) {
            $result = $this->view->generate($params);
        }

        return $result;
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
