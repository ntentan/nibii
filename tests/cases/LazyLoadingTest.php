<?php
namespace ntentan\nibii\tests\cases;

use ntentan\nibii\tests\models\Users;

class LazyLoadingTest extends \ntentan\nibii\tests\lib\RecordWrapperTestBase
{
    public function testLazyLoading()
    {
        $expected = [
            ['id' => 10, 'name' => 'Some test user'],
            ['id' => 11, 'name' => 'Matches'],
            ['id' => 12, 'name' => 'Rematch']
        ];
        $users = Users::fetch();
        $i = 0;
        foreach($users as $user)
        {
            $this->assertEquals(
                $expected[$i++],
                $user->{'\ntentan\nibii\tests\models\Roles'}->toArray()
            );
        }
    }
}
