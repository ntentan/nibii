<?php

namespace ntentan\nibii\tests\cases;

use ntentan\nibii\tests\models\raw\Users;
use ntentan\nibii\tests\models\raw\Roles;

class RecordWrapperTest extends \ntentan\nibii\tests\lib\RecordWrapperTestBase
{
    public function testTableResolution()
    {
        $users = new Users();
        $datastore = getenv('NIBII_DATASTORE');
        $description = $users->getDescription();
        $this->assertInstanceOf('\ntentan\nibii\ModelDescription', $description);
    }

    public function testCreate()
    {
        $this->assertEquals(6, $this->getConnection()->getRowCount('roles'));
        $role = Roles::createNew();
        $role->name = 'Super User';
        $this->assertArrayNotHasKey('id', $role->toArray());
        $this->assertEquals(true, $role->save());
        $this->assertArrayHasKey('id', $role->toArray());
        $this->assertTrue(is_numeric($role->toArray()['id']));
        $this->assertEquals(7, $this->getConnection()->getRowCount('roles'));

        $queryTable = $this->getConnection()->createQueryTable(
            'roles', 'SELECT name FROM roles ORDER BY name'
        );

        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'roles' => [
                    ['name' => 'Another test role'],
                    ['name' => 'Matches'],
                    ['name' => 'More test roles'],
                    ['name' => 'Rematch'],
                    ['name' => 'Some test user'],
                    ['name' => 'Super User'],
                    ['name' => 'Test role'],
                ]
            ])->getTable('roles'), $queryTable
        );
    }

    public function testCreate2()
    {
        $this->assertEquals(6, $this->getConnection()->getRowCount('roles'));
        $role = Roles::createNew();
        $role['name'] = 'Super User';
        $this->assertEquals(true, $role->save());
        $this->assertEquals(7, $this->getConnection()->getRowCount('roles'));

        $queryTable = $this->getConnection()->createQueryTable(
            'roles', 'SELECT name FROM roles ORDER BY name'
        );

        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'roles' => [
                    ['name' => 'Another test role'],
                    ['name' => 'Matches'],
                    ['name' => 'More test roles'],
                    ['name' => 'Rematch'],
                    ['name' => 'Some test user'],
                    ['name' => 'Super User'],
                    ['name' => 'Test role'],
                ]
            ])->getTable('roles'), $queryTable
        );
    }

    public function testFetch()
    {
        $role = Roles::fetch(10);
        $this->assertEquals(
            array(
                'id' => 10,
                'name' => 'Some test user',
            ), $role->toArray()
        );
        $this->assertInstanceOf('\\ntentan\\nibii\\RecordWrapper', $role);
        $this->assertTrue(is_numeric($role->toArray()['id']));

        $role = Roles::filterByName('Matches')->fetchFirst();
        $this->assertEquals(
            array(
                'id' => 11,
                'name' => 'Matches',
            ), $role->toArray()
        );

        $role = Roles::filterByName('Matches')->fetch();
        $this->assertEquals(array(
                array(
                    'id' => 11,
                    'name' => 'Matches',
                ),
            ), $role->toArray()
        );

        $role = Roles::filterByName('Matches', 'Rematch')->fetch();
        $this->assertEquals(array(
                array(
                    'id' => 11,
                    'name' => 'Matches',
                ),
                array(
                    'id' => 12,
                    'name' => 'Rematch',
                )
            ), $role->toArray()
        );

        $role = Roles::fetchWithName('Matches');
        $this->assertEquals(array(
                array(
                    'id' => 11,
                    'name' => 'Matches',
                ),
            ), $role->toArray()
        );

        $role = Roles::fetchFirstWithName('Matches');
        $this->assertEquals(
            array(
                'id' => 11,
                'name' => 'Matches',
            ), $role->toArray()
        );

        $this->assertEquals(1, count($role));
        $this->assertEquals(11, $role->id);
        $this->assertEquals(11, $role['id']);
        $this->assertArrayHasKey('id', $role);
        $this->assertArrayHasKey('name', $role);
        $this->assertArrayNotHasKey('other', $role);

        $users = Users::fetch();
        $this->assertEquals(4, count($users));
        $this->assertEquals(1, count($users[0]));

        $users = Users::fields('id', 'username')->filterByUsername('james')->fetchFirst();
        $this->assertEquals(1, count($users));
        $this->assertArrayNotHasKey('firstname', $users);
        $this->assertArrayHasKey('username', $users);
        $this->assertArrayHasKey('id', $users);
    }

    public function testFilterCompiler()
    {
        $user = Users::filter('username = ?', 'james')->fetchFirst();
        $this->assertEquals(1, count($user));
        $this->assertEquals('james', $user->username);

        $user = Users::filter('username = :username', [':username' => 'james'])->fetchFirst();
        $this->assertEquals(1, count($user));
        $this->assertEquals('james', $user->username);

        $user = Users::filter('username = ? or (role_id in(? , ?)', 'james', '10', '11')->fetch();

        $user = Users::filter('role_id between 10 and 13')->fetch();
        $this->assertEquals(4, count($user));

        $user = Users::filter('password = md5(?)', 'password')->fetch();
        $this->assertEquals(1, count($user));
    }

    public function testValidation()
    {
        $user = Users::createNew();
        $response = $user->save();
        $this->assertEquals(false, $response);
        $this->assertEquals(
            array (
                'email' =>
                array (
                  0 => 'The email field is required',
                ),
                'firstname' =>
                array (
                  0 => 'The firstname field is required',
                ),
                'lastname' =>
                array (
                  0 => 'The lastname field is required',
                ),
                'password' =>
                array (
                  0 => 'The password field is required',
                ),
                'role_id' =>
                array (
                  0 => 'The role_id field is required',
                ),
                'username' =>
                array (
                  0 => 'The username field is required',
                ),
            ),
            $user->getInvalidFields()
        );
    }

    public function testUpdate()
    {
        $user = Users::fetchFirstWithId(1);
        $user->username = 'jamie';
        $user->save();
        $queryTable = $this->getConnection()->createQueryTable(
            'users', 'SELECT username FROM users WHERE id = 1'
        );
        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'users' => [
                    ['username' => 'jamie']
                ]
            ])->getTable('users'),
            $queryTable
        );
    }

    public function testBulkUpdate()
    {
        Users::update(['role_id' => 15]);

        $queryTable = $this->getConnection()->createQueryTable(
            'users', 'SELECT role_id FROM users'
        );
        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'users' => [
                    ['role_id' => 15],
                    ['role_id' => 15],
                    ['role_id' => 15],
                    ['role_id' => 15]
                ]
            ])->getTable('users'),
            $queryTable
        );
    }

    public function testBulkUpdate2()
    {
        Users::filterByRoleId(11, 12)->update(['role_id' => 15]);
        $queryTable = $this->getConnection()->createQueryTable(
            'users', 'SELECT role_id FROM users ORDER BY role_id'
        );
        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'users' => [
                    ['role_id' => 10],
                    ['role_id' => 15],
                    ['role_id' => 15],
                    ['role_id' => 15]
                ]
            ])->getTable('users'),
            $queryTable
        );
    }

    public function testBulkUpdate3()
    {
        Users::filter('id < ?', 3)->update(['role_id' => 15]);
        $queryTable = $this->getConnection()->createQueryTable(
            'users', 'SELECT role_id FROM users ORDER BY role_id'
        );
        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'users' => [
                    ['role_id' => 12],
                    ['role_id' => 12],
                    ['role_id' => 15],
                    ['role_id' => 15]
                ]
            ])->getTable('users'),
            $queryTable
        );
    }

    public function testDelete()
    {
        Users::filter('id < ?', 3)->delete();
        $queryTable = $this->getConnection()->createQueryTable(
            'users', 'SELECT id, username, role_id, firstname FROM users'
        );
        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'users' => [
                    ['id' => 3, 'username' => 'kwame', 'role_id' => 12, 'firstname' => 'Kwame'],
                    ['id' => 4, 'username' => 'adjoa', 'role_id' => 12, 'firstname' => 'Adjoa'],
                ]
            ])->getTable('users'),
            $queryTable
        );
    }
}
