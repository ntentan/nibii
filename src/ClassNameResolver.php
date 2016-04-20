<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ntentan\nibii;

use ntentan\nibii\interfaces\ClassResolverInterface;
use ntentan\nibii\interfaces\ModelJoinerInterface;
use ntentan\nibii\interfaces\TableNameResolverInterface;

/**
 * Description of DefaultClassResolver
 *
 * @author ekow
 */
class ClassNameResolver implements ClassResolverInterface, ModelJoinerInterface,
    TableNameResolverInterface
{
    public function getModelClassName($default, $context)
    {
        if(self::$classResolver !== null && $model[0] !== "\\") {
            $resolver = self::$classResolver;
            $className = $resolver($model, $context);
        } else {
            $className = $model;
        }
        return $className;
    }
    
    private function getClassFileDetails($className)
    {
        $arrayed = explode('\\', $className);
        $class = array_pop($arrayed);
        if($arrayed[0] == '') {
            array_shift($arrayed);
        }
        return ['class' => $class, 'namespace' => implode('\\', $arrayed)];
    }

    public function getJunctionClassName($classA, $classB)
    {
        $classA = $this->getClassFileDetails($classA);
        $classB = $this->getClassFileDetails($classB);
        if($classA['namespace'] != $classB['namespace']) {
            throw new NibiiException(
                "Cannot automatically join two classes of different "
                    . "namespaces. Please provide a model joiner or "
                    . "explicitly specify your joint model."
            );
        }
        $classes = [$classA['class'], $classB['class']];
        sort($classes);
        return "{$classA['namespace']}\\" . implode('', $classes);        
    }

    public function getTableName($instance)
    {
        $class = new \ReflectionClass($instance);
        $nameParts = explode("\\", $class->getName());
        return \ntentan\utils\Text::deCamelize(end($nameParts));           
    }

}
