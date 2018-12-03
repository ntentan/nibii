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

/**
 * DriverAdapter provides a generic interface through which specific database operations can be performed.
 * It.
 */
abstract class DriverAdapter
{
    protected $data;
    private $insertQuery;
    private $updateQuery;
    private $modelInstance;
    protected $queryEngine;
    private $driver;

    public function setContext(ORMContext $context)
    {
        $this->driver = $context->getDbContext()->getDriver();
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Convert datatypes from the database system's native type to a generic type
     * supported by nibii.
     *
     * @param string $nativeType The native datatype
     *
     * @return string The generic datatype for use in nibii.
     */
    abstract public function mapDataTypes($nativeType);

    /**
     * @param QueryParameters $parameters
     *
     * @return type
     */
    public function select($parameters)
    {
        $result = $this->driver->query(
            $this->getQueryEngine()->getSelectQuery($parameters),
            $parameters->getBoundData()
        );

        if ($parameters->getFirstOnly() && isset($result[0])) {
            $result = $result[0];
        }

        return $result;
    }

    /**
     * @param QueryParameters $parameters
     */
    public function count($parameters)
    {
        $result = $this->driver->query($this->getQueryEngine()->getCountQuery($parameters), $parameters->getBoundData());

        return $result[0]['count'];
    }

    private function initInsert()
    {
        $this->insertQuery = $this->getQueryEngine()->getInsertQuery($this->modelInstance);
    }

    private function initUpdate()
    {
        $this->updateQuery = $this->getQueryEngine()->getUpdateQuery($this->modelInstance);
    }

    public function insert($record)
    {
        if ($this->insertQuery === null) {
            $this->initInsert();
        }

        return $this->driver->query($this->insertQuery, $record);
    }

    public function update($record)
    {
        if ($this->updateQuery === null) {
            $this->initUpdate();
        }

        return $this->driver->query($this->updateQuery, $record);
    }

    /**
     * @param QueryParameters $parameters
     */
    public function bulkUpdate($data, $parameters)
    {
        return $this->driver->query(
            $this->getQueryEngine()->getBulkUpdateQuery($data, $parameters), array_merge($data, $parameters->getBoundData())
        );
    }

    /**
     * @param QueryParameters $parameters
     */
    public function delete($parameters)
    {
        return $this->driver->query(
            $this->getQueryEngine()->getDeleteQuery($parameters), $parameters->getBoundData()
        );
    }

    public function describe($model, $relationships)
    {
        return new ModelDescription(
            $this->driver->describeTable($table)[$table], $relationships, function ($type) {
                return $this->mapDataTypes($type);
            }
        );
    }

    /**
     * @return \ntentan\nibii\QueryEngine
     */
    private function getQueryEngine()
    {
        if ($this->queryEngine === null) {
            $this->queryEngine = new QueryEngine();
            $this->queryEngine->setDriver($this->driver);
        }

        return $this->queryEngine;
    }

    /**
     * @param RecordWrapper $model
     */
    public function setModel($model)
    {
        $this->modelInstance = $model;
    }

    public function getDriver()
    {
        return $this->driver;
    }
}
