<?php
namespace PAVApp\Core;

class Request implements RequestInterface
{
    public function get(): array
    {
        return $_GET;
    }

    public function post(): array
    {
        return $_POST;
    }
}