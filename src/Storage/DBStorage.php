<?php
namespace PAVApp\Storage;

use App\Config\Storage as StorageConfig;

// using "Singleton" pattern
final class DBStorage implements StorageInterface
{
    private static $instance = null;
    
    private function __construct()
    {
    }
    
    public static function getInstance(): ?object
    {
        try {
            return self::$instance === null ? 
                self::$instance = new \PDO(
                    "mysql:host=".StorageConfig::DB_HOST.";dbname=".StorageConfig::DB_NAME,
                    StorageConfig::DB_USER,
                    StorageConfig::DB_PASSW
                ) : 
                self::$instance;
        } catch (\Exception $Err) {
            return null;
        }
    }
}