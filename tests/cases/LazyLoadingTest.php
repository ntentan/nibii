<?php
namespace ntentan\nibii\tests\cases;

use ntentan\nibii\tests\models\Users;

error_reporting(E_ALL);

class LazyLoadingTest extends \ntentan\nibii\tests\lib\RecordWrapperTestBase
{   
    public function testLazyLoading()
    {
        $users = Users::fetch();
        foreach($users as $user)
        {
            //var_dump($user->{'\ntentan\nibii\tests\models\Roles'});
        }
    }    
}
