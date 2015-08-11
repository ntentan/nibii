<?php
namespace ntentan\nibii\tests\models\raw;

class Roles extends \ntentan\nibii\RecordWrapper
{
    protected $hasMany = ['\ntentan\nibii\tests\models\raw\Users'];
}
