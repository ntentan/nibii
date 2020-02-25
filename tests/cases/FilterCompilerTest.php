<?php

/*
 * The MIT License
 *
 * Copyright 2014-2018 James Ekow Abaka Ainooson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ntentan\nibii\tests\cases;

use ntentan\nibii\exceptions\FilterCompilerException;
use ntentan\nibii\FilterCompiler;
use PHPUnit\Framework\TestCase;

class FilterCompilerTest extends TestCase
{
    private $compiler;

    public function setUp(): void
    {
        $this->compiler = new FilterCompiler();
    }

    public function testCharacterException()
    {
        $this->expectException(FilterCompilerException::class);
        $this->compiler->compile("name = 'james'");
    }

    public function testTokenException()
    {
        $this->expectException(FilterCompilerException::class);
        $this->compiler->compile('name = ? james');
    }

    public function testExpectedException()
    {
        $this->expectException(FilterCompilerException::class);
        $this->compiler->compile('in ?');
    }

    public function testCastExpression()
    {
        $this->assertEquals(
            'name = cast(name as varchar)', $this->compiler->compile('name = cast(name as varchar)')
        );

        $this->assertEquals(
            'name = concat(cast(:filter_bind_1 as char), cast(:filter_bind_2 as char))',
            $this->compiler->compile('name = concat(cast(? as char), cast(? as char))')
        );

        $this->assertEquals(
            'id = random()', $this->compiler->compile('id = random()')
        );
    }
}
