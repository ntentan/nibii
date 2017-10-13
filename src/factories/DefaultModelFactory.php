<?php

namespace ntentan\nibii\factories;

use ntentan\utils\Text;
use ntentan\nibii\interfaces\ModelFactoryInterface;

class DefaultModelFactory implements ModelFactoryInterface
{

    public function createModel($className, $context)
    {
        return new $className();
    }

    public function getClassName($model)
    {
        return $model;
    }

    public function getModelTable($instance)
    {
        $class = new \ReflectionClass($instance);
        $nameParts = explode("\\", $class->getName());
        return Text::deCamelize(end($nameParts));
    }

    public function getJunctionClassName($classA, $classB)
    {
        $classA = $this->getClassFileDetails($classA);
        $classB = $this->getClassFileDetails($classB);
        if ($classA['namespace'] != $classB['namespace']) {
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
    
    private function getClassFileDetails($className) {
        $arrayed = explode('\\', $className);
        $class = array_pop($arrayed);
        if ($arrayed[0] == '') {
            array_shift($arrayed);
        }
        return ['class' => $class, 'namespace' => implode('\\', $arrayed)];
    }    

}
