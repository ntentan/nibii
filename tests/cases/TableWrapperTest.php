<?php
namespace ntentan\nibii\tests\cases;

class TableWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        \ntentan\nibii\Nibii::setDefaultDatastoreSettings(
            [
                'datastore' => 'mysql',
                'dbname' => 'ntentan_tests',
                'host' => 'localhost',
                'user' => 'root',
                'password' => 'root',
                'file' => null
            ]
        );
    }
    
    public function testTableResolution()
    {
        $users = new \ntentan\nibii\tests\classes\Users();
        var_dump($users->getFields());
    }
}