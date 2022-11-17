<?php
namespace PAVApp\Core;

interface ResultInterface
{
    public function setError(string $error): void;
    public function setData(array $data): void;
    public function getError(): string;
    public function getData(): array;
}
