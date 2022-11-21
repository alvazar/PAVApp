<?php
namespace PAVApp\Interfaces;

interface FactoryInterface
{
    public static function get(): ?object;
}
