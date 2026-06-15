<?php

namespace ntentan\nibii\tests\models\raw;

class Users extends \ntentan\nibii\ActiveRecord
{
    protected $belongsTo = ['\ntentan\nibii\tests\models\raw\Roles'];
}
