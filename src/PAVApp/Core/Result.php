<?php
namespace PAVApp\Core;

use PAVApp\Traits\ErrorTrait;

class Result implements ResultInterface
{
    use ErrorTrait;

    protected $data = [];

    public function setData(array $data): void
    {
        $this->data = $data;
    }
    
    public function getData(): array
    {
        return $this->data;
    }
}
