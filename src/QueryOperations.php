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

use ntentan\utils\Text;

class QueryOperations {

    /**
     *
     * @var RecordWrapper
     */
    private $wrapper;
    private $adapter;
    private $queryParameters;
    private $pendingMethod;
    private $dynamicMethods = [
        "/(?<method>filterBy)(?<variable>[A-Z][A-Za-z]+){1}/",
        "/(?<method>sort)(?<direction>Asc|Desc)?(By)(?<variable>[A-Z][A-Za-z]+){1}/",
        "/(?<method>fetch)(?<first>First)?(With)(?<variable>[A-Za-z]+)/"
    ];
    private $dataOperations;
    private $driver;

    /**
     * 
     * @param RecordWrapper $wrapper
     * @param DriverAdapter $adapter
     * @param DataOperations $dataOperations
     */
    public function __construct(ORMContext $context, RecordWrapper $wrapper, DriverAdapter $adapter, $dataOperations) {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;
        $this->dataOperations = $dataOperations;
        $this->driver = $context->getDbContext()->getDriver();
    }

    public function doFetch($id = null) {
        $parameters = $this->getFetchQueryParameters($id);
        $data = $this->adapter->select($parameters);
        $this->wrapper->setData($data);
        $this->resetQueryParameters();
        return $this->wrapper;
    }

    private function getFetchQueryParameters($arg) {
        if ($arg instanceof \ntentan\nibii\QueryParameters) {
            return $arg;
        }        
        
        $parameters = $this->getQueryParameters();
        
        if (is_numeric($arg)) {
            $description = $this->wrapper->getDescription();
            $parameters->addFilter($description->getPrimaryKey()[0], [$arg]);
            $parameters->setFirstOnly(true);
        } else if (is_array($arg)) {
            foreach ($arg as $field => $value) {
                $parameters->addFilter($field, [$value]);
            }
        }
        
        return $parameters;
    }

    /**
     *
     * @return \ntentan\nibii\QueryParameters
     */
    private function getQueryParameters($instantiate = true) {
        if ($this->queryParameters === null && $instantiate) {
            $this->queryParameters = new QueryParameters($this->wrapper->getDBStoreInformation()['quoted_table']);
        }
        return $this->queryParameters;
    }

    private function resetQueryParameters() {
        $this->queryParameters = null;
    }

    public function doFetchFirst($id = null) {
        $this->getQueryParameters()->setFirstOnly(true);
        return $this->doFetch($id);
    }

    public function doFields() {
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

    private function getFilter($arguments) {
        if (count($arguments) == 2 && is_array($arguments[1])) {
            $filter = $arguments[0];
            $data = $arguments[1];
        } else {
            $filter = array_shift($arguments);
            $data = $arguments;
        }
        return ['filter' => $filter, 'data' => $data];
    }

    public function doFilter() {
        $arguments = func_get_args();
        $details = $this->getFilter($arguments);
        $filterCompiler = new FilterCompiler();
        $this->getQueryParameters()->setRawFilter(
                $filterCompiler->compile($details['filter']), $filterCompiler->rewriteBoundData($details['data'])
        );
        return $this->wrapper;
    }

    public function doFilterBy() {
        $arguments = func_get_args();
        $details = $this->getFilter($arguments);
        $this->getQueryParameters()->addFilter($details['filter'], $details['data']);
        return $this->wrapper;
    }

    public function doUpdate($data) {
        $this->driver->beginTransaction();
        $parameters = $this->getQueryParameters();
        $this->adapter->bulkUpdate($data, $parameters);
        $this->driver->commit();
        $this->resetQueryParameters();
    }

    public function doDelete() {
        $this->driver->beginTransaction();
        $parameters = $this->getQueryParameters(false);

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

    public function runDynamicMethod($arguments) {
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

    public function initDynamicMethod($method) {
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

    public function doCount() {
        return $this->adapter->count($this->getQueryParameters());
    }

    public function doLimit($numItems) {
        $this->getQueryParameters()->setLimit($numItems);
        return $this->wrapper;
    }

    public function doOffset($offset) {
        $this->getQueryParameters()->setOffset($offset);
        return $this->wrapper;
    }
    
    public function doWith($model) {
        $relationship = $this->wrapper->getDescription()->getRelationships()[$model];
        return $relationship->getQuery();
    }

}
