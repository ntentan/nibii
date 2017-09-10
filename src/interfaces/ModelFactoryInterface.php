<?php
/**
 * Created by PhpStorm.
 * User: ekow
 * Date: 9/5/17
 * Time: 8:14 AM
 */

namespace ntentan\nibii\interfaces;


interface ModelFactoryInterface
{
    public function createModel($name, $context);
    public function getModelTable($instance);
}