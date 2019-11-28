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

namespace ntentan\nibii;

use ntentan\nibii\exceptions\NibiiException;

class Operations
{
    private $wrapper;

    /**
     * @var \ntentan\nibii\DriverAdapter
     */
    private $adapter;

    /**
     * @var QueryOperations
     */
    private $queryOperations;

    /**
     * @var DataOperations
     */
    private $dataOperations;

    /**
     * @var array
     */
    private $queryOperationMethods = [
        'fetch', 'fetchFirst', 'filter', 'query', 'fields',
        'cover', 'limit', 'offset', 'filterBy', 'sortBy',
        'delete', 'count', 'update', 'with',
    ];
    private $dataOperationMethods = [
        'save', 'validate',
    ];

    public function __construct(RecordWrapper $wrapper, $table)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $wrapper->getAdapter();
        $driver = ORMContext::getInstance()->getDbContext()->getDriver();
        $this->dataOperations = new DataOperations($wrapper, $driver);
        $this->queryOperations = new QueryOperations($wrapper, $this->dataOperations, $driver);
    }

    public function perform($name, $arguments)
    {
        //@todo Think of using a hash here in future
        if (array_search($name, $this->queryOperationMethods) !== false) {
            return call_user_func_array([$this->queryOperations, "do$name"], $arguments);
        } elseif (array_search($name, $this->dataOperationMethods) !== false) {
            return call_user_func_array([$this->dataOperations, "do$name"], $arguments);
        } elseif ($this->queryOperations->initDynamicMethod($name)) {
            return $this->queryOperations->runDynamicMethod($arguments);
        } else {
            throw new NibiiException("Method {$name} not found");
        }
    }

    public function getData()
    {
        return $this->dataOperations->getData();
    }

    public function getInvalidFields()
    {
        return $this->dataOperations->getInvalidFields();
    }
}
