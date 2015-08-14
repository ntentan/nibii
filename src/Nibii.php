<?php

namespace ntentan\nibii;

class Nibii
{
    private static $classResolver;

    public static function load($path)
    {
        return (new \ReflectionClass(self::getClassName($path)))->newInstance();
    }

    public static function getClassName($model, $context = null)
    {
        if(self::$classResolver !== null && $model[0] !== "\\") {
            $resolver = self::$classResolver;
            $className = $resolver($model, $context);
        } else {
            $className = $model;
        }
        return $className;
    }

    public static function setClassResolver($classResolver)
    {
        self::$classResolver = $classResolver;
    }
}
