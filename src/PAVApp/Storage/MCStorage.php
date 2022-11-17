<?php
namespace PAVApp\Storage;

use App\Config\Storage as StorageConfig;

class MCStorage implements StorageInterface
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
