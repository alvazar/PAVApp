<?php
namespace PAVApp\Interfaces;

interface SingletonInterface
{
    public static function getInstance(): ?object;
}
