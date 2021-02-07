<?php
namespace PAVApp\Core;

interface RequestInterface
{
    public function type(): string;
    public function getData(): array;
    public function getQuery(): string;
    public function getVars(string $param): array;
    public function hasParam(string $param): bool;
}