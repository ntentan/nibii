<?php

namespace ntentan\nibii\tests\cases;

use ntentan\nibii\tests\classes\Users;
use ntentan\nibii\tests\classes\Roles;

error_reporting(E_ALL);

class RecordWrapperTest extends \PHPUnit_Extensions_Database_TestCase
{

    public function setUp()
    {
        parent::setUp();
        \ntentan\nibii\DriverAdapter::setDefaultSettings(
            [
                'datastore' => getenv('NIBII_DATASTORE'),
                'host' => getenv('NIBII_HOST'),
                'user' => getenv('NIBII_USER'),
                'password' => getenv('NIBII_PASSWORD'),
                'file' => getenv('NIBII_FILE'),
                'dbname' => getenv("NIBII_DBNAME")
            ]
        );
    }

    public function tearDown()
    {
        \ntentan\nibii\DriverAdapter::reset();
    }

    public function testTableResolution()
    {
        $users = new Users();
        $datastore = getenv('NIBII_DATASTORE');
        require __DIR__ . "/../expected/$datastore/users_description.php";
        $this->assertEquals($description, $users->getDescription());
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
        $this->assertEquals(3, count($users));
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
        $this->assertEquals(3, count($user));

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
                    ['role_id' => 15],
                    ['role_id' => 15]
                ]
            ])->getTable('users'),
            $queryTable
        );
    }    

    protected function getConnection()
    {
        $pdo = new \PDO(getenv('NIBII_PDO_DSN'), getenv('NIBII_USER'), getenv('NIBII_PASSWORD'));
        return $this->createDefaultDBConnection($pdo, getenv('NIBII_DBNAME'));
    }

    protected function getDataSet()
    {
        return $this->createArrayDataSet([
            'roles' => [
                ['id' => 10, 'name' => 'Some test user'],
                ['id' => 11, 'name' => 'Matches'],
                ['id' => 12, 'name' => 'Rematch'],
                ['id' => 13, 'name' => 'Test role'],
                ['id' => 14, 'name' => 'Another test role'],
                ['id' => 15, 'name' => 'More test roles']
            ],
            'users' => [
                ['id' => 1, 'username' => 'james', 'role_id' => 10, 'firstname' => 'James', 'lastname' => 'Ainooson', 'status' => 1, 'password' => 'somehashedstring', 'email' => 'james@nibii.test'],
                ['id' => 2, 'username' => 'fiifi', 'role_id' => 11, 'firstname' => 'Fiifi', 'lastname' => 'Antobra', 'status' => 2, 'password' => md5('password'), 'email' => 'fiifi@nibii.test'],
                ['id' => 3, 'username' => 'kwame', 'role_id' => 12, 'firstname' => 'Kwame', 'lastname' => 'Nyarko', 'status' => 2, 'password' => 'coolthings', 'email' => 'knyarko@nibii.test']
            ]
        ]);
    }

    protected function getSetUpOperation()
    {
        return $this->getOperations()->CLEAN_INSERT(TRUE);
    }
}
