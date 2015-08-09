<?php

namespace ntentan\nibii;

class Nibii
{
    private static $classResolver;
    
    public static function load($path)
    {
        return (new \ReflectionClass(self::getClassName($path)))->newInstance();
    }
    
    public static function getClassName($path) 
    {
        if(self::$classResolver !== null && $path[0] !== "\\") {
            $className = self::$classResolver($path);
        } else {
            $className = $path;
        }         
        return $className;
    }
    
    public static function setClassResolver($classResolver)
    {
        self::$classResolver = $classResolver;
    }        
}