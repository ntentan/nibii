<?php
namespace ntentan\nibii\tests\cases;

use ntentan\nibii\FilterCompiler;

class FilterCompilerTest extends \PHPUnit_Framework_TestCase {
    /**
     * @expectedException \ntentan\nibii\FilterCompilerException
     */
    public function testCharacterException() {
        FilterCompiler::compile("name = 'james'");
    }
    
    /**
     * @expectedException \ntentan\nibii\FilterCompilerException
     */
    public function testTokenException() {
        FilterCompiler::compile("name = ? james");
    }
    
    /**
     * @expectedException \ntentan\nibii\FilterCompilerException
     */
    public function testExpectedException() {
        FilterCompiler::compile("in ?");
    }    
    
    public function testCastExpression() {
        $this->assertEquals(
            'name = cast(name as varchar)', 
            FilterCompiler::compile("name = cast(name as varchar)")
        );
        
        $this->assertEquals(
            'name = concat(cast(? as char), cast(? as char))',
            FilterCompiler::compile("name = concat(cast(? as char), cast(? as char))")
        );
        
        $this->assertEquals(
            'id = random()',
            FilterCompiler::compile("id = random()")
        );        
    }
}