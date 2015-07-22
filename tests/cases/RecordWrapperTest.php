<?php
namespace ntentan\nibii\tests\cases;

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
    
    public function testTableResolution()
    {
        $users = new \ntentan\nibii\tests\classes\Users();
        $datastore = getenv('NIBII_DATASTORE');
        require __DIR__ . "/../expected/$datastore/users_description.php";
        $this->assertEquals($description, $users->getDescription());
    }
    
    public function testCreate()
    {
        $this->assertEquals(3, $this->getConnection()->getRowCount('roles'));
        $role = \ntentan\nibii\tests\classes\Roles::createNew();
        $role->name = 'Super User';
        $role->save();
        $this->assertEquals(4, $this->getConnection()->getRowCount('roles'));
        $queryTable = $this->getConnection()->createQueryTable(
            'roles', 'SELECT name FROM roles ORDER BY name'
        );
        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'roles' => [
                    ['name' => 'Matches'],
                    ['name' => 'Rematch'],
                    ['name' => 'Some test user'],
                    ['name' => 'Super User'],
                ]
            ])->getTable('roles'), 
            $queryTable
        );
    }
    
    public function testCreate2()
    {
        $this->assertEquals(3, $this->getConnection()->getRowCount('roles'));
        $role = \ntentan\nibii\tests\classes\Roles::createNew();
        $role['name'] = 'Super User';
        $role->save();
        $this->assertEquals(4, $this->getConnection()->getRowCount('roles'));
        $queryTable = $this->getConnection()->createQueryTable(
            'roles', 'SELECT name FROM roles ORDER BY name'
        );
        $this->assertTablesEqual(
            $this->createArrayDataSet([
                'roles' => [
                    ['name' => 'Matches'],
                    ['name' => 'Rematch'],
                    ['name' => 'Some test user'],
                    ['name' => 'Super User'],
                ]
            ])->getTable('roles'), 
            $queryTable
        );
    }    
    
    public function testFetch()
    {
        $role = \ntentan\nibii\tests\classes\Roles::fetch(10);
        $this->assertEquals(
            array(
                'id' => 10,
                'name' => 'Some test user',
            ),
            $role->getData()
        );
        $this->assertInstanceOf('\\ntentan\\nibii\\RecordWrapper', $role);
        //$this->assertInternalType('int', $role->getData()['id']);
        
        $role = \ntentan\nibii\tests\classes\Roles::filterByName('Matches')->fetchFirst();
        $this->assertEquals(
            array(
                'id' => 11,
                'name' => 'Matches',
            ),
            $role->getData()
        );        
        
        $role = \ntentan\nibii\tests\classes\Roles::filterByName('Matches')->fetch();
        $this->assertEquals(array (
                array (
                    'id' => 11,
                    'name' => 'Matches',
                ),
            ),
            $role->getData()
        );
        
        $role = \ntentan\nibii\tests\classes\Roles::filterByName('Matches', 'Rematch')->fetch();
        $this->assertEquals(array (
                array (
                    'id' => 11,
                    'name' => 'Matches',
                ),
                array (
                    'id' => 12,
                    'name' => 'Rematch',
                )
            ),
            $role->getData()
        );        
        
        $role = \ntentan\nibii\tests\classes\Roles::fetchWithName('Matches');
        $this->assertEquals(array (
                array (
                    'id' => 11,
                    'name' => 'Matches',
                ),
            ),
            $role->getData()
        );      
        
        $role = \ntentan\nibii\tests\classes\Roles::fetchFirstWithName('Matches');
        $this->assertEquals(
            array (
                'id' => 11,
                'name' => 'Matches',
            ),
            $role->getData()
        );  
        
        $this->assertEquals(1, count($role));
        $this->assertEquals(11, $role->id);
        $this->assertEquals(11, $role['id']);
        $this->assertArrayHasKey('id', $role);
        $this->assertArrayHasKey('name', $role);
        $this->assertArrayNotHasKey('other', $role);
        
        $users = \ntentan\nibii\tests\classes\Users::fetch();
        $this->assertEquals(2, count($users));
        $this->assertEquals(1, count($users[0]));
        
        $users = \ntentan\nibii\tests\classes\Users::fields('id', 'username')->filterByUsername('james')->fetchFirst();
        $this->assertEquals(1, count($users));
        $this->assertArrayNotHasKey('firstname', $users);
        $this->assertArrayHasKey('username', $users);
        $this->assertArrayHasKey('id', $users);
    }
    
    public function testFilterCompiler()
    {
        $user = \ntentan\nibii\tests\classes\Users::filter('username = ?', 'james')->fetchFirst();
        $this->assertEquals(1, count($user));
        $this->assertEquals('james', $user->username); 
        
        $user = \ntentan\nibii\tests\classes\Users::filter('username = :username', [':username' => 'james'])->fetchFirst();
        $this->assertEquals(1, count($user));
        $this->assertEquals('james', $user->username);                
        
        $user = \ntentan\nibii\tests\classes\Users::filter('username = ? or (role_id in(? , ?)', 'james', '10', '11')->fetch();
        $this->assertEquals(2, count($user));
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
                ['id' => 12, 'name' => 'Rematch']
            ],
            'users' => [
                ['id' => 1, 'username' => 'james', 'role_id' => 10, 'firstname' => 'James', 'lastname' => 'Ainooson', 'status' => 1, 'password' => 'somehashedstring', 'email' => 'james@nibii.test'],
                ['id' => 2, 'username' => 'fiifi', 'role_id' => 11, 'firstname' => 'Fiifi', 'lastname' => 'Antobra', 'status' => 2, 'password' => 'somehashedstring', 'email' => 'fiifi@nibii.test']
            ]
        ]);
    }
    
    protected function getSetUpOperation()
    {
        return $this->getOperations()->CLEAN_INSERT(TRUE);
    }
}