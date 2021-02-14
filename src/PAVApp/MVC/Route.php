<?php
namespace PAVApp\MVC;

use PAVApp\Core\RequestInterface;
use PAVApp\Core\ResultInterface;
use PAVApp\MVC\RouteInterface;

class Route implements RouteInterface
{
    private static $getCB = [];
    private static $postCB = [];

    public static function run(mixed $cb, array $params = []): ResultInterface
    {
        if (is_string($cb) && preg_match("/\./u", $cb) === 1) {
            [$cl, $mt] = explode('.', $cb);
            return (new $cl($params))->$mt();
        }
        return $cb($params);
    }

    public static function get(string $queryTrigger, mixed $cb, array $params = []): void
    {
        $triggers = explode(',', $queryTrigger);
        foreach ($triggers as $trigger) {
            $trigger = trim($trigger);
            self::$getCB[$trigger] = [$cb, $params];
        }
    }

    public static function post(string $queryTrigger, mixed $cb, array $params = []): void
    {
        $triggers = explode(',', $queryTrigger);
        foreach ($triggers as $trigger) {
            $trigger = trim($trigger);
            self::$postCB[$trigger] = [$cb, $params];
        }
    }

    public static function start(RequestInterface $Req): void
    {
        $lst = [];
        if ($Req->type() === 'GET') {
            $lst = self::$getCB;
        } elseif ($Req->type() === 'POST') {
            $lst = self::$postCB;
        }
        foreach ($lst as $queryTrigger => $item) {
            if ($Req->hasParam($queryTrigger)) {
                $Result = self::run(
                    $item[0],
                    $item[1] + $Req->getVars($queryTrigger) + $Req->getData()
                );
                $data = $Result->getData();
                if (isset($data['output'])) {
                    print $data['output'];
                }
                break;
            }
        }
    }

    public static function redirect(string $url = ""): void
    {
        header("Location: ".$url);
        exit;
    }
}