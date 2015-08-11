<?php
namespace ntentan\nibii\tests\models\aliased;

class Roles extends \ntentan\nibii\RecordWrapper
{
    protected $hasMany = [['\ntentan\nibii\tests\models\aliased\Users', 'as' => 'users']];
}
