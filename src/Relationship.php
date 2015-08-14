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
    
    protected $name;
    protected $foreignKey;
    protected $localKey;
    protected $model;
    protected $belongsTo;
    protected $hasMany;
    protected $belongsToMany;
    protected $type;

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setForeignKey($foreignKey)
    {
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public function setLocalKey($localKey)
    {
        $this->localKey = $localKey;
        return $this;
    }

    public function getModelInstance()
    {
        $class = Nibii::getClassName($this->model, $this->type);
        return new $class();
    }

    abstract public function getQuery($data);
    abstract public function setup($table, $primaryKey);
}
