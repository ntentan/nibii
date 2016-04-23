<?php
namespace ntentan\nibii\tests\lib;

class RecordWrapperTestBase extends \PHPUnit_Extensions_Database_TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        \ntentan\atiaa\Db::reset();
        \ntentan\panie\InjectionContainer::resetSingletons();
    }

    protected function getConnection()
    {
        $pdo = new \PDO(getenv('NIBII_PDO_DSN'), getenv('NIBII_USER'), getenv('NIBII_PASSWORD'));
        return $this->createDefaultDBConnection($pdo, getenv('NIBII_DBNAME'));
    }

    protected function getDataSet()
    {
        return $this->createArrayDataSet(array_merge($this->getExtraArrayData(),[
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
                ['id' => 3, 'username' => 'kwame', 'role_id' => 12, 'firstname' => 'Kwame', 'lastname' => 'Nyarko', 'status' => 2, 'password' => 'coolthings', 'email' => 'knyarko@nibii.test'],
                ['id' => 4, 'username' => 'adjoa', 'role_id' => 12, 'firstname' => 'Adjoa', 'lastname' => 'Boateng', 'status' => 2, 'password' => 'hahaha', 'email' => 'aboateng@nibii.test']
            ]
        ]));
    }

    protected function getSetUpOperation()
    {
        return $this->getOperations()->CLEAN_INSERT(TRUE);
    }
    
    protected function getExtraArrayData()
    {
        return [];
    }
}
