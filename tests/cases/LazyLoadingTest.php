<?php
namespace ntentan\nibii\tests\cases;

class LazyLoadingTest extends \ntentan\nibii\tests\lib\RecordWrapperTestBase
{
    public function testBelongsTo()
    {
        $expected = [
            ['id' => 10, 'name' => 'Some test user'],
            ['id' => 11, 'name' => 'Matches'],
            ['id' => 12, 'name' => 'Rematch'],
            ['id' => 12, 'name' => 'Rematch']
        ];
        $users = \ntentan\nibii\tests\models\raw\Users::fetch();
        $i = 0;
        foreach($users as $user)
        {
            $this->assertEquals(
                $expected[$i++],
                $user->{'\ntentan\nibii\tests\models\raw\Roles'}->toArray()
            );
        }
    }

    public function testHasMany()
    {
        $role = \ntentan\nibii\tests\models\raw\Roles::fetch(12);
        $results = array (
          0 =>
          array (
            'id' => 3,
            'username' => 'kwame',
            'password' => 'coolthings',
            'role_id' => 12,
            'firstname' => 'Kwame',
            'lastname' => 'Nyarko',
            'othernames' => NULL,
            'status' => 2,
            'email' => 'knyarko@nibii.test',
            'phone' => NULL,
            'office' => NULL,
            'last_login_time' => NULL,
            'is_admin' => NULL,
          ),
          1 =>
          array (
            'id' => 4,
            'username' => 'adjoa',
            'password' => 'hahaha',
            'role_id' => 12,
            'firstname' => 'Adjoa',
            'lastname' => 'Boateng',
            'othernames' => NULL,
            'status' => 2,
            'email' => 'aboateng@nibii.test',
            'phone' => NULL,
            'office' => NULL,
            'last_login_time' => NULL,
            'is_admin' => NULL,
          ),
        );
        $this->assertEquals($results, $role->{'\ntentan\nibii\tests\models\raw\Users'}->toArray());
    }

    public function testAliasedBelongsTo()
    {
        $expected = [
            ['id' => 10, 'name' => 'Some test user'],
            ['id' => 11, 'name' => 'Matches'],
            ['id' => 12, 'name' => 'Rematch'],
            ['id' => 12, 'name' => 'Rematch']
        ];
        $users = \ntentan\nibii\tests\models\aliased\Users::fetch();
        $i = 0;
        foreach($users as $user)
        {
            $this->assertEquals(
                $expected[$i++],
                $user->role->toArray()
            );
        }
    }

    public function testAliasedHasMany()
    {
        $role = \ntentan\nibii\tests\models\aliased\Roles::fetch(12);
        $results = array (
          0 =>
          array (
            'id' => 3,
            'username' => 'kwame',
            'password' => 'coolthings',
            'role_id' => 12,
            'firstname' => 'Kwame',
            'lastname' => 'Nyarko',
            'othernames' => NULL,
            'status' => 2,
            'email' => 'knyarko@nibii.test',
            'phone' => NULL,
            'office' => NULL,
            'last_login_time' => NULL,
            'is_admin' => NULL,
          ),
          1 =>
          array (
            'id' => 4,
            'username' => 'adjoa',
            'password' => 'hahaha',
            'role_id' => 12,
            'firstname' => 'Adjoa',
            'lastname' => 'Boateng',
            'othernames' => NULL,
            'status' => 2,
            'email' => 'aboateng@nibii.test',
            'phone' => NULL,
            'office' => NULL,
            'last_login_time' => NULL,
            'is_admin' => NULL,
          ),
        );
        $this->assertEquals($results, $role->users->toArray());
    }
}
