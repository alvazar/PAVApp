<?php
namespace PAVApp\Core;

class Template
{
    public function __construct()
    {
    }
    
    protected function getBlockTemplate(string $name, string $template): string
    {
        $regexp = "/(\<\!\-\-\s?(b\[{$name}\])\s?\{\s?\-\-\>.+\<\!\-\-\s?\}\s?\g{2}\s?\-\-\>)/us";
        preg_match_all($regexp, $template, $match);
        return !empty($match[1][0]) ? $match[1][0] : "";
    }
    
    public function make(string $path, array $data, string $onlyBlock = ""): string
    {
        $template = file_get_contents($path);
        return $this->makeTemplate($template, $data, $onlyBlock);
    }

    public function makeTemplate(string $template, array $data, string $onlyBlock = ""): string
    {
        if ($onlyBlock !== "") {
            $template = $this->getBlockTemplate($onlyBlock, $template);
        }
        foreach ($data as $name => $value) {
            $template = $this->blockReplace($name, $value, $template);
        }
        return $template;
    }
    
    protected function blockReplace(string $name, array $data, string $template): string
    {
        $blockTemplate = $this->getBlockTemplate($name, $template);
        $blockMaked = $blockTemplate;
        
        // remove block
        if ($data === false) {
            return str_replace($blockMaked, "", $template);
        }
        
        $isMultiple = is_array($data) && isset($data[0]);
        
        // prepare
        if ($isMultiple) {
            $blockMaked = "";
            foreach ($data as $value) {
                if (is_array($value)) {
                    $blockMaked .= $this->blockReplace($name, $value, $blockTemplate);
                }
            }
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value) || $value === false) {
                    // prepare sub block
                    $blockMaked = $this->blockReplace($key, $value, $blockMaked);
                } else {
                    // replace mask on value
                    $flag = "";
                    if (strpos($key,":") !== false) {
                        list($key, $flag) = explode(":", $key, 2);
                    }
                    $value = $this->prepareValue($value, $flag);
                    $blockMaked = str_replace('<!-- v['.$key.'] -->', $value, $blockMaked);
                }
            }
        }
        
        // replace block
        $template = str_replace($blockTemplate, $blockMaked, $template);
        
        return $template;
    }

    public function skipTemplateTags(string $content): string
    {
        $regexp = "/\<\!\-\-(\s?\}|)\s?[bv]\[[^\]]+\]\s?(\{\s?|)\-\-\>/us";
        return preg_replace($regexp, "", $content);
    }

    public function prepareValue(string $value, string $flag): string
    {
        if ($flag !== "raw") {
            $value = htmlspecialchars($value);
        }
        return $value;
    }
}