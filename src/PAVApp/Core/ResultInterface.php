<?php
namespace PAVApp\Core;

interface ResultInterface
{
    public function set(string $key, $value): self;

    public function get(string $key): mixed;

    public function setData(array $data): self;
    
    public function getData(): array;

    public function setError(string $error): self;

    public function getError(): string;

    public function hasError(): bool;
}
