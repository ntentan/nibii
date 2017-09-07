<?php
/**
 * Created by PhpStorm.
 * User: ekow
 * Date: 9/5/17
 * Time: 7:51 AM
 */

namespace ntentan\nibii;

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
        return \ntentan\utils\Text::deCamelize(end($nameParts));
    }
}