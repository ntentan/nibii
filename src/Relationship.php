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

/**
 * Relationships provide queries for fetching data from related models when using lazy loading.
 *
 * @package ntentan\nibii
 */
abstract class Relationship
{

    /**
     * Constant for the belongs-to or one-to many relation ship.
     */
    const BELONGS_TO = 'BelongsTo';


    const HAS_MANY = 'HasMany';
    const MANY_HAVE_MANY = 'ManyHaveMany';

    protected $options = [];
    protected $type;
    protected $setupName;
    protected $setupTable;
    protected $setupSchema;
    protected $setupPrimaryKey;

    private $setup = false;
    private $query;
    protected $queryPrepared = false;

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function getQuery()
    {
        if (!$this->query) {
            $this->query = new QueryParameters();
        }
        return $this->query;
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Gets an instance of the related model accessed through this class.
     * @return RecordWrapper
     */
    public function getModelInstance()
    {
        if (!$this->setup) {
            $this->runSetup();
            $this->setup = true;
        }
        return ORMContext::getInstance()->getModelFactory()->createModel($this->options['model'], $this->type);
    }

    public function setup($name, $schema, $table, $primaryKey)
    {
        $this->setupName = $name;
        $this->setupTable = $table;
        $this->setupPrimaryKey = $primaryKey;
        $this->setupSchema = $schema;
    }

    public function preSave(&$wrapper, $value)
    {

    }

    public function postSave(&$wrapper)
    {

    }

    abstract public function prepareQuery($data);

    abstract public function runSetup();
}
