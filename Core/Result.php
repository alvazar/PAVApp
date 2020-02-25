<?php
namespace PAVApp\Core;

class Result implements ResultInterface
{
    protected $error = '';
    protected $data = [];

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getError(): string
    {
        return $this->error;
    }
    
    public function getData(): array
    {
        return $this->data;
    }
}