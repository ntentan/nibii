<?php

namespace ntentan\nibii;

use ntentan\panie\InjectionContainer;

class Nibii
{
    public static function load($path)
    {
        return InjectionContainer::resolve(self::getClassName($path));
    }
    
    public static function joinModels($classA, $classB)
    {
        return InjectionContainer::singleton(interfaces\ModelJoinerInterface::class)->getJunctionClassName($classA, $classB);
    }
    
    public static function getModelTable($instance)
    {
        return InjectionContainer::singleton(interfaces\TableNameResolverInterface::class)->getTableName($instance);
    }

    public static function getClassName($model, $context = null)
    {
        return InjectionContainer::singleton(interfaces\ClassResolverInterface::class)->getModelClassName($model, $context);
    }
    
    public static function getModelName($class)
    {
        return $class;
    }
    
    public static function setupDefaultBindings()
    {
        InjectionContainer::bind(interfaces\ModelJoinerInterface::class)->to(ClassNameResolver::class);
        InjectionContainer::bind(interfaces\TableNameResolverInterface::class)->to(ClassNameResolver::class);
        InjectionContainer::bind(interfaces\ClassResolverInterface::class)->to(ClassNameResolver::class);
    }
}
