<?php
namespace ntentan\nibii\interfaces;

interface ModelFactoryInterface
{
    public function createModel($name, $context);

    public function getModelTable($instance);

    public function getClassName($model);

    public function getJunctionClassName($classA, $classB);
}
