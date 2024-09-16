<?php
namespace ntentan\nibii\interfaces;

use ntentan\nibii\RecordWrapper;

interface ModelFactoryInterface
{
    /**
     * Create an instance of the model when given a name.
     * @param $name
     * @param $context
     * @return mixed
     */
    public function createModel(string $name, string $context): RecordWrapper;

    public function getModelTable(RecordWrapper $instance): string;

    public function getClassName(string $model): string;

    public function getJunctionClassName(string $classA, string $classB): string;
}
