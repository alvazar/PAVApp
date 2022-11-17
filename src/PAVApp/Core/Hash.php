<?php
namespace PAVApp\Core;

class Hash
{
    private $key;

    public function __construct(string $key)
    {
        $this->$key = $key;
    }

    public function generate(array $params, int $minutes = 0, string $salt = ""): string
    {
        $preHash = $this->prepareHash($params, $salt);
        $hash = md5($preHash);
        
        // add lifetime
        if ($minutes > 0) {
            $timeEnd = strtotime(sprintf("+%d minutes", (int) $minutes));
            $hash = $this->encodeTime($hash, $timeEnd);
        }
        
        // salt
        if ($salt !== "") {
            $hash .= sprintf("_%s", (string) $salt);
        }
        
        return $hash;
    }

    public function check(array $params, string $hash): string
    {
        // salt
        $salt = "";

        if (preg_match("/\_/u", $hash) === 1) {
            $salt = preg_replace("/.+\_/u", "", $hash);
        }
        
        //
        $preHash = $this->prepareHash($params, $salt);
        $repairHash = md5($preHash);
        
        // check lifetime
        $timeCheck = true;

        if (preg_match("/\:/u", $hash) === 1) {
            $timeEnd = $this->getTime($hash);
            $repairHash = $this->encodeTime($repairHash, $timeEnd);
            $timeCheck = time() < $timeEnd;
        }
        
        if ($salt !== "") {
            $repairHash .= sprintf("_%s", $salt);
        }
        
        return $repairHash === $hash && $timeCheck;
    }
    
    private function prepareHash(array $params, string $salt = ""): string
    {
        $result = "";

        foreach ($params as $key => $value) {
            $result .= sprintf("%s=%s_", $key, $value);
        }

        $result .= $this->key;

        if ($salt !== "") {
            $result .= sprintf("_%s", (string) $salt);
        }

        return $result;
    }
    
    private function encodeTime(string $hash, int $timeEnd): string
    {
        $timeEnd = (string) $timeEnd;
        $encodeHash = "";
        
        $timeLength = mb_strlen($timeEnd);

        for ($i = 0; $i < $timeLength; $i += 1) {
            $encodeHash .= $hash[$i].$timeEnd[$i];
        }

        $encodeHash .= ":".mb_substr($hash, $timeLength);

        return $encodeHash;
    }
    
    private function getTime(string $hash): int
    {
        $result = "";
        list($encodeHash, $hashEnd) = explode(":",$hash);
        $length = mb_strlen($encodeHash);

        for ($i = 1; $i < $length; $i += 2) {
            $result .= $encodeHash[$i];
        }
        
        return (int) $result;
    }
}
