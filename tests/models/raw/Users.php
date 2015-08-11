<?php
namespace ntentan\nibii\tests\models\raw;

class Users extends \ntentan\nibii\RecordWrapper
{
    protected $belongsTo = ['\ntentan\nibii\tests\models\raw\Roles'];
}
