<?php

namespace ntentan\nibii\factories;

use ntentan\utils\Text;

class DefaultModelFactory implements ModelFactoryInterface
{
    public function createModel($className)
    {
        return new $className();
    }

    public function getModelTable($instance)
    {
        $class = new \ReflectionClass($instance);
        $nameParts = explode("\\", $class->getName());
        return Text::deCamelize(end($nameParts));
    }
}