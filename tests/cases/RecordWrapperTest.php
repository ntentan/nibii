<?php
namespace ntentan\nibii\tests\cases;

use ntentan\nibii\ORMContext;
use ntentan\nibii\tests\lib\RecordWrapperTestBase;
use ntentan\nibii\tests\models\raw\Roles;
use ntentan\nibii\tests\models\raw\Users;

class RecordWrapperTest extends RecordWrapperTestBase
{
    public function testTableResolution()
    {
        $users = ORMContext::getInstance()->load(Users::class);
        $description = $users->getDescription();
        $this->assertInstanceOf('\ntentan\nibii\ModelDescription', $description);
    }

    public function testCreate()
    {
        $this->assertEquals(6, $this->countTableRows('roles'));
        $role = new \ntentan\nibii\tests\models\aliased\Roles();
        $role->name = 'Super User';
        $this->assertArrayNotHasKey('id', $role->toArray());
        $this->assertEquals(true, $role->save());
        $this->assertArrayHasKey('id', $role->toArray());
        $this->assertTrue(is_numeric($role->toArray()['id']));
        $this->assertEquals(7, $this->countTableRows('roles'));

        $queryTable = $this->getQueryAsArray('SELECT name FROM roles ORDER BY name');

        $this->assertEquals([
                ['name' => 'Another test role'],
                ['name' => 'Matches'],
                ['name' => 'More test roles'],
                ['name' => 'Rematch'],
                ['name' => 'Some test user'],
                ['name' => 'Super User'],
                ['name' => 'Test role']
            ], $queryTable
        );
    }

    public function testCreate2()
    {
        $this->assertEquals(6, $this->countTableRows('roles'));
        $role = new Roles();
        $role['name'] = 'Super User';
        $this->assertEquals(true, $role->save());
        $this->assertEquals(7, $this->countTableRows('roles'));

        $queryTable = $this->getQueryAsArray('SELECT name FROM roles ORDER BY name');

        $this->assertEquals([
                ['name' => 'Another test role'],
                ['name' => 'Matches'],
                ['name' => 'More test roles'],
                ['name' => 'Rematch'],
                ['name' => 'Some test user'],
                ['name' => 'Super User'],
                ['name' => 'Test role'],
            ],
            $queryTable
        );
    }

    public function testFetch()
    {
        $role = (new Roles())->fetch(10);
        $this->assertEquals(
                [
            'id'   => 10,
            'name' => 'Some test user',
                ], $role->toArray()
        );
        $this->assertInstanceOf('\\ntentan\\nibii\\RecordWrapper', $role);
        $this->assertTrue(is_numeric($role->toArray()['id']));

        $role = (new Roles())->filterByName('Matches')->fetchFirst();
        $this->assertEquals(
                [
            'id'   => 11,
            'name' => 'Matches',
                ], $role->toArray()
        );

        $role = (new Roles())->filterByName('Matches')->fetch();
        $this->assertEquals([
            [
                'id'   => 11,
                'name' => 'Matches',
            ],
                ], $role->toArray()
        );

        $role = (new Roles())->filterByName('Matches', 'Rematch')->fetch();
        $this->assertEquals([
            [
                'id'   => 11,
                'name' => 'Matches',
            ],
            [
                'id'   => 12,
                'name' => 'Rematch',
            ],
                ], $role->toArray()
        );

        $role = (new Roles())->fetchWithName('Matches');
        $this->assertEquals([
            [
                'id'   => 11,
                'name' => 'Matches',
            ],
                ], $role->toArray()
        );

        $role = (new Roles())->fetchFirstWithName('Matches');
        $this->assertEquals(
                [
            'id'   => 11,
            'name' => 'Matches',
                ], $role->toArray()
        );

        $this->assertEquals(1, count($role));
        $this->assertEquals(11, $role->id);
        $this->assertEquals(11, $role['id']);
        $this->assertArrayHasKey('id', $role);
        $this->assertArrayHasKey('name', $role);
        $this->assertArrayNotHasKey('other', $role);

        $users = (new Users())->fetch();
        $this->assertEquals(4, count($users->toArray()));
        $this->assertEquals(1, count($users[0]));

        $users = (new Users())->fields('id', 'username')->filterByUsername('james')->fetchFirst();
        $this->assertEquals(1, count($users));
        $this->assertArrayNotHasKey('firstname', $users);
        $this->assertArrayHasKey('username', $users);
        $this->assertArrayHasKey('id', $users);
    }

    public function testFilterCompiler()
    {
        $user = (new Users())->filter('username = ?', 'james')->fetchFirst();
        $this->assertEquals(1, count($user));
        $this->assertEquals('james', $user->username);

        $user = (new Users())->filter('username = :username', [':username' => 'james'])->fetchFirst();
        $this->assertEquals(1, count($user));
        $this->assertEquals('james', $user->username);

        $user = (new Users())->filter('username = ? or (role_id in(? , ?)', 'james', '10', '11')->fetch();

        $user = (new Users())->filter('role_id between 10 and 13')->fetch();
        $this->assertEquals(4, count($user));

        $user = (new Users())->filter('password = md5(?)', 'password')->fetch();
        $this->assertEquals(1, count($user));
    }

    public function testValidation()
    {
        $user = new Users();
        $response = $user->save();
        $this->assertEquals(false, $response);
        $this->assertEquals(
                [
                    'email' => [
                        0 => 'The email field is required',
                    ],
                    'firstname' => [
                        0 => 'The firstname field is required',
                    ],
                    'lastname' => [
                        0 => 'The lastname field is required',
                    ],
                    'password' => [
                        0 => 'The password field is required',
                    ],
                    'role_id' => [
                        0 => 'The role_id field is required',
                    ],
                    'username' => [
                        0 => 'The username field is required',
                    ],
                ], $user->getInvalidFields()
        );
    }

    public function testUniqueValidation()
    {
        $role = new Roles();
        $role->name = 'Matches';
        $response = $role->save();

        $this->assertEquals(false, $response);
        $this->assertEquals(
                [
            'name' => [
                0 => 'The value of name must be unique',
            ],
                ], $role->getInvalidFields()
        );
    }

    public function testUpdate()
    {
        $user = (new Users())->fetchFirstWithId(1);
        $user->username = 'jamie';
        $user->save();
        $queryTable = $this->getQueryAsArray('SELECT username FROM users WHERE id = 1');
        $this->assertEquals([['username' => 'jamie']], $queryTable);
    }

    public function testBulkUpdate()
    {
        (new Users())->update(['role_id' => 15]);

        $queryTable = $this->getQueryAsArray('SELECT role_id FROM users');
        $this->assertEquals([
                ['role_id' => 15],
                ['role_id' => 15],
                ['role_id' => 15],
                ['role_id' => 15],
            ], $queryTable
        );
    }

    public function testBulkUpdate2()
    {
        (new Users())->filterByRoleId(11, 12)->update(['role_id' => 15]);
        $queryTable = $this->getQueryAsArray('SELECT role_id FROM users ORDER BY role_id');
        $this->assertEquals([
                ['role_id' => 10],
                ['role_id' => 15],
                ['role_id' => 15],
                ['role_id' => 15],
            ], $queryTable
        );
    }

    public function testBulkUpdate3()
    {
        (new Users())->filter('id < ?', 3)->update(['role_id' => 15]);
        $queryTable = $this->getQueryAsArray('SELECT role_id FROM users ORDER BY role_id');
        $this->assertEquals([
                ['role_id' => 12],
                ['role_id' => 12],
                ['role_id' => 15],
                ['role_id' => 15],
            ], $queryTable
        );
    }

    public function testDelete()
    {
        (new Users())->filter('id < ?', 3)->delete();
        $queryTable = $this->getQueryAsArray('SELECT id, username, role_id, firstname FROM users');
        $this->assertEquals([
                ['id' => 3, 'username' => 'kwame', 'role_id' => 12, 'firstname' => 'Kwame'],
                ['id' => 4, 'username' => 'adjoa', 'role_id' => 12, 'firstname' => 'Adjoa'],
            ],
            $queryTable
        );
    }
}
