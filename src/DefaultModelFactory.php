<?php
/**
 * Created by PhpStorm.
 * User: ekow
 * Date: 9/5/17
 * Time: 7:51 AM
 */

namespace ntentan\nibii;


class DefaultModelFactory implements ModelFactoryInterface
{
    public function createModel($className)
    {
        return new $className();
    }
}