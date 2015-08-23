<?php

namespace ntentan\nibii;

class Nibii
{
    private static $classResolver;
    private static $modelResolver;
    private static $modelJoiner;

    public static function load($path)
    {
        return (new \ReflectionClass(self::getClassName($path)))->newInstance();
    }
    
    private static function getClassFileDetails($className)
    {
        $arrayed = explode('\\', $className);
        $class = array_pop($arrayed);
        if($arrayed[0] == '') {
            array_shift($arrayed);
        }
        return ['class' => $class, 'namespace' => implode('\\', $arrayed)];
    }
    
    public static function joinModels($classA, $classB)
    {
        if(self::$modelJoiner) {
            $modelJoiner = self::$modelJoiner;
            return $modelJoiner($classA, $classB);
        } else {
            $classA = self::getClassFileDetails($classA);
            $classB = self::getClassFileDetails($classB);
            if($classA['namespace'] != $classB['namespace']) {
                throw new NibiiException("Cannot automatically join two classes of different namespaces. Please provide a model joiner or explicitly specify your joint model.");
            }
            $classes = [$classA['class'], $classB['class']];
            sort($classes);
            return "{$classA['namespace']}\\" . implode('', $classes);
        }
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
    
    public static function getModelName($class)
    {
        if(self::$modelResolver) {
            $modelResolver = self::$modelResolver;
            return $modelResolver($class);
        } else {
            return $class;
        }
    }

    public static function setClassResolver($classResolver)
    {
        self::$classResolver = $classResolver;
    }
}
