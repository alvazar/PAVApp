<?php
namespace PAVApp\Storage;

use App\Config\Storage as StorageConfig;
use PAVApp\Interfaces\SingletonInterface;

class MCStorage implements SingletonInterface
{
    private static $instance = null;

    private function __construct()
    {
    }
    
    public static function getInstance(): ?object
    {
        if (self::$instance === null) {
            self::$instance = new \Memcache();
            self::$instance->connect(StorageConfig::MC_HOST, StorageConfig::MC_PORT);
        }

        return self::$instance;
    }
}
