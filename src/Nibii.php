<?php

namespace ntentan\nibii;

use ntentan\panie\InjectionContainer;

class Nibii
{
    public static function load($path)
    {
        return (new \ReflectionClass(self::getClassName($path)))->newInstance();
    }
    
    public static function joinModels($classA, $classB)
    {
        return InjectionContainer::singleton(interfaces\ModelJoinerInterface::class)->getJunctionClass($classA, $classB);
    }
    
    public static function getModelTable($instance)
    {
        return InjectionContainer::singleton(interfaces\TableNameResolverInterface::class)->getTableName($instance);
    }

    public static function getClassName($model, $context = null)
    {
        return InjectionContainer::singleton(interfaces\ClassResolverInterface::class)->getClassName($model, $context);
    }
    
    public static function getModelName($class)
    {
        return $class;
    }
}
