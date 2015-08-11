<?php
namespace ntentan\nibii\tests\models\aliased;

class Users extends \ntentan\nibii\RecordWrapper
{
    protected $belongsTo = [
        ['\ntentan\nibii\tests\models\aliased\Roles', 'as' => 'role'],
        ['\ntentan\nibii\tests\models\aliased\Departments', 'as' => 'department', 'local_key' => 'office']
    ];
}
