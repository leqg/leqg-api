<?php
class Configuration
{
    static $configuration;
    
    public static function read($name)
    {
        return self::$configuration[$name];
    }
    
    public static function write($name, $value)
    {
        self::$configuration[$name] = $value;
    }
}
