<?php
namespace PAVApp\Core;

use PAVApp\Traits\DataTrait;
use PAVApp\Traits\ErrorTrait;

class Result implements ResultInterface
{
    use ErrorTrait, DataTrait;
}
