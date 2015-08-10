<?php
namespace ntentan\nibii\tests\models;

class Users extends \ntentan\nibii\RecordWrapper
{
    protected $belongsTo = ['\ntentan\nibii\tests\models\Roles'];
}
