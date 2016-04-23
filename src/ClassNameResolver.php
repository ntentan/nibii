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
use ntentan\config\Config;
use ntentan\utils\Text;

/**
 * Description of DefaultClassResolver
 *
 * @author ekow
 */
class ClassNameResolver implements ClassResolverInterface, ModelJoinerInterface,
    TableNameResolverInterface
{
    public function getModelClassName($className, $context)
    {
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
    
    public static function getDriverAdapterClassName()
    {
        $driver = Config::get('ntentan:db.driver', false);
        if($driver) {
            return __NAMESPACE__ . '\adapters\\' . Text::ucamelize(Config::get('ntentan:db.driver')) . 'Adapter';
        } 
        throw new NibiiException("Please specify a driver");
    }
}
