<?php

/*
 * The MIT License
 *
 * Copyright 2015 ekow.
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

namespace ntentan\nibii;

abstract class Relationship
{
    const BELONGS_TO = 'BelongsTo';
    const HAS_MANY = 'HasMany';
    const MANY_HAVE_MANY = 'ManyHaveMany';
    
    protected $options = [];
    protected $type;
    protected $setupName;
    protected $setupTable;
    protected $setupPrimaryKey;
    
    private $setup = false;

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function getModelInstance()
    {
        if(!$this->setup) {
            $this->runSetup();
            $this->setup = true;
        }
        $class = Nibii::getClassName($this->options['model'], $this->type);
        return new $class();
    }
    
    public function setup($name, $table, $primaryKey) 
    {
        $this->setupName = $name;
        $this->setupTable = $table;
        $this->setupPrimaryKey = $primaryKey;
    }

    abstract public function getQuery($data);
    abstract public function runSetup();
    
}
