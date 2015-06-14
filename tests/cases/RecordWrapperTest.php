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
        $role = \ntentan\nibii\tests\classes\Roles::createNew();
        $role->name = 'Super User';
        $role->save();
    }
    
    public function testFetch()
    {
        $role = \ntentan\nibii\tests\classes\Roles::fetch(10);
        $this->assertEquals(
            array(
                'id' => '10',
                'name' => 'Some test user',
            ),
            $role->getData()
        );
        $this->assertInstanceOf('\\ntentan\\nibii\\RecordWrapper', $role);
        
        $role = \ntentan\nibii\tests\classes\Roles::filterByName('Matches')->fetch();
        $this->assertEquals(array (
                array (
                    'id' => '11',
                    'name' => 'Matches',
                ),
            ),
            $role->getData()
        );
        
        $role = \ntentan\nibii\tests\classes\Roles::fetchWithName('Matches');
        $this->assertEquals(array (
                array (
                    'id' => '11',
                    'name' => 'Matches',
                ),
            ),
            $role->getData()
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
                ['id' => 12, 'name' => 'Rematch']
            ],
        ]);
    }
    
    //protected function getSetUpOperation()
    //{
        //return \PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL();
    //}
}