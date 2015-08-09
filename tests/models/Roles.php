<?php
namespace ntentan\nibii\tests\models;

class Roles extends \ntentan\nibii\RecordWrapper
{
    protected $hasMany = '\ntentan\nibii\tests\models\Users';
}
