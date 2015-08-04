<?php

namespace ntentan\nibii\tests\cases;

use ntentan\nibii\FilterCompiler;

class FilterCompilerTest extends \PHPUnit_Framework_TestCase
{
    private $compiler;
    
    public function setUp()
    {
        $this->compiler = new FilterCompiler();
    }
    
    /**
     * @expectedException \ntentan\nibii\FilterCompilerException
     */
    public function testCharacterException()
    {
        $this->compiler->compile("name = 'james'");
    }

    /**
     * @expectedException \ntentan\nibii\FilterCompilerException
     */
    public function testTokenException()
    {
        $this->compiler->compile("name = ? james");
    }

    /**
     * @expectedException \ntentan\nibii\FilterCompilerException
     */
    public function testExpectedException()
    {
        $this->compiler->compile("in ?");
    }

    public function testCastExpression()
    {
        $this->assertEquals(
            'name = cast(name as varchar)', $this->compiler->compile("name = cast(name as varchar)")
        );

        $this->assertEquals(
            'name = concat(cast(:filter_bind_1 as char), cast(:filter_bind_2 as char))', 
            $this->compiler->compile("name = concat(cast(? as char), cast(? as char))")
        );

        $this->assertEquals(
            'id = random()', $this->compiler->compile("id = random()")
        );
    }

}
