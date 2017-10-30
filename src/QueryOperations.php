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

use ntentan\atiaa\Driver;
use ntentan\utils\Text;

/**
 * Performs operations that query the database.
 *
 * @package ntentan\nibii
 */
class QueryOperations
{

    /**
     * An instance of the record wrapper being used.
     * @var RecordWrapper
     */
    private $wrapper;

    /**
     * An instance of the driver adapter used in the database connection.
     * @var DriverAdapter
     */
    private $adapter;

    /**
     * An instance of query parameters used to perform the various queries.
     * @var QueryParameters
     */
    private $queryParameters;

    /**
     * The name of a method initialized through a dynamic method waiting to be executed.
     * @var string
     */
    private $pendingMethod;

    /**
     * Regular expressions for matching dynamic methods.
     * @var array
     */
    private $dynamicMethods = [
        "/(?<method>filterBy)(?<variable>[A-Z][A-Za-z]+){1}/",
        "/(?<method>sort)(?<direction>Asc|Desc)?(By)(?<variable>[A-Z][A-Za-z]+){1}/",
        "/(?<method>fetch)(?<first>First)?(With)(?<variable>[A-Za-z]+)/"
    ];

    /**
     * An instance of the dataoperations used for filtered deletes.
     * @var DataOperations
     */
    private $dataOperations;

    /**
     * An instance of the database driver used for the connection.
     * @var Driver
     */
    private $driver;

    /**
     * QueryOperations constructor
     * @param RecordWrapper $wrapper
     * @param DataOperations $dataOperations
     * @param Driver $driver
     * @internal param DriverAdapter $adapter
     */
    public function __construct(RecordWrapper $wrapper, DataOperations $dataOperations, Driver $driver)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $wrapper->getAdapter();
        $this->dataOperations = $dataOperations;
        $this->driver = $driver;
    }

    /**
     * @param int|array|QueryParameters $query
     * @return RecordWrapper
     */
    public function doFetch($query = null)
    {
        $parameters = $this->getFetchQueryParameters($query);
        $data = $this->adapter->select($parameters);
        $this->wrapper->setData($data);
        $this->resetQueryParameters();
        return $this->wrapper;
    }

    private function getFetchQueryParameters($arg, $instantiate = true)
    {
        if ($arg instanceof QueryParameters) {
            return $arg;
        }

        $parameters = $this->getQueryParameters($instantiate);

        if (is_numeric($arg)) {
            $description = $this->wrapper->getDescription();
            $parameters->addFilter($description->getPrimaryKey()[0], $arg);
            $parameters->setFirstOnly(true);
        } else if (is_array($arg)) {
            foreach ($arg as $field => $value) {
                $parameters->addFilter($field, $value);
            }
        }

        return $parameters;
    }

    /**
     *
     * @return QueryParameters
     */
    private function getQueryParameters($instantiate = true)
    {
        if ($this->queryParameters === null && $instantiate) {
            $this->queryParameters = new QueryParameters($this->wrapper->getDBStoreInformation()['quoted_table']);
        }
        return $this->queryParameters;
    }

    private function resetQueryParameters()
    {
        $this->queryParameters = null;
    }

    public function doFetchFirst($id = null)
    {
        $this->getQueryParameters()->setFirstOnly(true);
        return $this->doFetch($id);
    }

    public function doFields()
    {
        $fields = [];
        $arguments = func_get_args();
        foreach ($arguments as $argument) {
            if (is_array($argument)) {
                $fields = array_merge($fields, $argument);
            } else {
                $fields[] = $argument;
            }
        }
        $this->getQueryParameters()->setFields($fields);
        return $this->wrapper;
    }

    public function doSortBy($field, $direction = 'ASC')
    {
        $this->getQueryParameters()->addSort($field, $direction);
    }

    private function getFilter($arguments)
    {
        if (count($arguments) == 2 && is_array($arguments[1])) {
            $filter = $arguments[0];
            $data = $arguments[1];
        } else {
            $filter = array_shift($arguments);
            $data = $arguments;
        }
        return ['filter' => $filter, 'data' => $data];
    }

    public function doFilter()
    {
        $arguments = func_get_args();
        $details = $this->getFilter($arguments);
        $this->getQueryParameters()->setFilter($details['filter'], $details['data']);
        return $this->wrapper;
    }

    public function doFilterBy()
    {
        $arguments = func_get_args();
        $details = $this->getFilter($arguments);
        $this->getQueryParameters()->addFilter($details['filter'], $details['data']);
        return $this->wrapper;
    }

    public function doUpdate($data)
    {
        $this->driver->beginTransaction();
        $parameters = $this->getQueryParameters();
        $this->adapter->bulkUpdate($data, $parameters);
        $this->driver->commit();
        $this->resetQueryParameters();
    }

    public function doDelete($args = null)
    {
        $this->driver->beginTransaction();
        $parameters = $this->getFetchQueryParameters($args);

        if ($parameters === null) {
            $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
            $parameters = $this->getQueryParameters();
            $data = $this->wrapper->getData();
            $keys = [];

            foreach ($data as $datum) {
                if ($this->dataOperations->isItemDeletable($primaryKey, $datum)) {
                    $keys[] = $datum[$primaryKey[0]];
                }
            }

            $parameters->addFilter($primaryKey[0], $keys);
            $this->adapter->delete($parameters);
        } else {
            $this->adapter->delete($parameters);
        }

        $this->driver->commit();
        $this->resetQueryParameters();
    }

    public function runDynamicMethod($arguments)
    {
        $arguments = count($arguments) > 1 ? $arguments : $arguments[0];
        switch ($this->pendingMethod['method']) {
            case 'filterBy':
                $this->getQueryParameters()->addFilter(Text::deCamelize($this->pendingMethod['variable']), $arguments);
                return $this->wrapper;
            case 'sort':
                $this->getQueryParameters()->addSort(Text::deCamelize($this->pendingMethod['variable']), $this->pendingMethod['direction']);
                return $this->wrapper;
            case 'fetch':
                $parameters = $this->getQueryParameters();
                $parameters->addFilter(Text::deCamelize($this->pendingMethod['variable']), $arguments);
                if ($this->pendingMethod['first'] === 'First') {
                    $parameters->setFirstOnly(true);
                }
                return $this->doFetch();
        }
    }

    public function initDynamicMethod($method)
    {
        $return = false;

        foreach ($this->dynamicMethods as $regexp) {
            if (preg_match($regexp, $method, $matches)) {
                $return = true;
                $this->pendingMethod = $matches;
                break;
            }
        }

        return $return;
    }

    public function doCount($query = null)
    {
        return $this->adapter->count($this->getFetchQueryParameters($query));
    }

    public function doLimit($numItems)
    {
        $this->getQueryParameters()->setLimit($numItems);
        return $this->wrapper;
    }

    public function doOffset($offset)
    {
        $this->getQueryParameters()->setOffset($offset);
        return $this->wrapper;
    }

    public function doWith($model)
    {
        $relationship = $this->wrapper->getDescription()->getRelationships()[$model];
        return $relationship->getQuery();
    }

}
