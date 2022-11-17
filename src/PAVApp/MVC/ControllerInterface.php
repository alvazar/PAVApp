<?php
namespace PAVApp\MVC;

use PAVApp\Core\ResultInterface;

interface ControllerInterface
{
    public function actionDefault(): ResultInterface;
}
