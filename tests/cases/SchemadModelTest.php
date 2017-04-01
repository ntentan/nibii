<?php

namespace ntentan\nibii\tests\cases;

use ntentan\nibii\interfaces\TableNameResolverInterface;
use ntentan\nibii\Resolver;
use ntentan\nibii\tests\lib\TestTableNameResolver;
use ntentan\nibii\tests\models\schemas\Schemad;
use ntentan\panie\InjectionContainer;

class SchemadModelTest extends \PHPUnit_Framework_TestCase {
    
    public function setUp() {
        InjectionContainer::bind(TableNameResolverInterface::class)
            ->to(TestTableNameResolver::class);
    }
    
    public function tearDown() {
        InjectionContainer::bind(TableNameResolverInterface::class)
            ->to(Resolver::class);        
    }
    
    public function testTable() {
        if(getenv('NIBII_NO_SCHEMAS') == 'yes') {
            $this->markTestSkipped();
            return;
        }        
        $model = Schemad::fetch();
    }
}
