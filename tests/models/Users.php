<?php
namespace ntentan\nibii\tests\models;

class Users extends \ntentan\nibii\RecordWrapper
{
    public function init()
    {
        $this->addBelongsTo('\ntentan\nibii\tests\models\Roles');
    }
}