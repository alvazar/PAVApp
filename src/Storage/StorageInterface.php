<?php
namespace PAVApp\Storage;

interface StorageInterface
{
    public static function getInstance(): ?object;
}