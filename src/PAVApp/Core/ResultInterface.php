<?php
namespace PAVApp\Core;

interface ResultInterface
{
    public function setData(array $data): void;
    public function getData(): array;
}
