<?php
namespace ntentan\nibii\factories;

use ntentan\nibii\interfaces\ModelFactoryInterface;
use ntentan\nibii\RecordWrapper;
use ntentan\nibii\relationships\RelationshipType;
use ntentan\utils\Text;

class DefaultModelFactory implements ModelFactoryInterface
{
    public function createModel(string $className, RelationshipType $context): RecordWrapper
    {
        return new $className();
    }

    public function getClassName(string $model): string
    {
        return $model;
    }

    public function getModelTable(RecordWrapper $instance): string
    {
        $class = new \ReflectionClass($instance);
        $nameParts = explode('\\', $class->getName());

        return Text::deCamelize(end($nameParts));
    }

    public function getJunctionClassName(string $classA, string $classB): string
    {
        $classA = $this->getClassFileDetails($classA);
        $classB = $this->getClassFileDetails($classB);
        if ($classA['namespace'] != $classB['namespace']) {
            throw new NibiiException(
            'Cannot automatically join two classes of different '
            .'namespaces. Please provide a model joiner or '
            .'explicitly specify your joint model.'
            );
        }
        $classes = [$classA['class'], $classB['class']];
        sort($classes);

        return "{$classA['namespace']}\\".implode('', $classes);
    }

    private function getClassFileDetails($className)
    {
        $arrayed = explode('\\', $className);
        $class = array_pop($arrayed);
        if ($arrayed[0] == '') {
            array_shift($arrayed);
        }

        return ['class' => $class, 'namespace' => implode('\\', $arrayed)];
    }
}
