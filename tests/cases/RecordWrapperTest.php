<?php
namespace ntentan\nibii\tests\cases;

class RecordWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        \ntentan\nibii\DriverAdapter::setDefaultSettings(
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
        require "tests/fixtures/{$_ENV['NIBII_DATASTORE']}/users_description.php";
        $this->assertEquals($description, $users->getDescription());
    }
    
    public function testCreate()
    {
        $role = \ntentan\nibii\tests\classes\Roles::getNew();
        $role->name = 'Super User';
        $role->save();
    }
}