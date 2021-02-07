<?php
namespace PAVApp\Core;

class Loader
{
    private $namespaces;
    public function __construct(array $namespaces = [])
    {
        $this->namespaces = $namespaces;
    }

    public function start(string $cl)
    {
        $path = '';
        foreach ($this->namespaces as $prefix => $baseDir) {
            $path = preg_replace('/^\\\?'.preg_quote($prefix).'/', '', $cl, 1, $cnt);
            if ($cnt > 0) {
                $path = str_replace("\\", "/", $path);
                $path = sprintf(
                    "%s%s%s.php",
                    $_SERVER['DOCUMENT_ROOT'],
                    $baseDir,
                    $path
                );
                break;
            }
        }
        if ($path !== '' && file_exists($path)) {
            require $path;
        }
    }
}