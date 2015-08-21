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

class QueryOperations 
{
    private $wrapper;
    private $adapter;
    private $queryParameters;
    private $pendingMethod;
    
    private $dynamicMethods = 
        [ "/(?<method>filterBy)(?<variable>[A-Z][A-Za-z]+){1}/",
        "/(?<method>sort)(?<direction>Asc|Desc)?(By)(?<variable>[A-Z][A-Za-z]+){1}/",
        "/(?<method>fetch)(?<first>First)?(With)(?<variable>[A-Za-z]+)/" ];
    
    public function __construct($wrapper, $adapter)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;
    }
    
    public function doFetch($id = null)
    {
        $parameters = $this->getFetchQueryParameters($id);
        $data = $this->adapter->select($parameters);
        $this->wrapper->setData($data);
        $this->resetQueryParameters();
        return $this->wrapper;
    }
    
    private function getFetchQueryParameters($arg)
    {
        if($arg === null) {
            $parameters = $this->getQueryParameters();
        } else {
            if (is_numeric($arg)) {
                $parameters = $this->getQueryParameters();
                $description = $this->wrapper->getDescription();
                $parameters->addFilter($description->getPrimaryKey()[0], [$arg]);
                $parameters->setFirstOnly(true);
            }
            else if($arg instanceof \ntentan\nibii\QueryParameters)
            {
                $parameters = $arg;
            }
        }
        return $parameters;
    }
    
    /**
     *
     * @return \ntentan\nibii\QueryParameters
     */
    private function getQueryParameters($instantiate = true)
    {
        if ($this->queryParameters === null && $instantiate) {
            $this->queryParameters = new QueryParameters($this->wrapper);
        }
        return $this->queryParameters;
    }
    
    private function resetQueryParameters()
    {
        $this->queryParameters = null;
    }    
    
    public function doFetchFirst()
    {
        $this->getQueryParameters()->setFirstOnly(true);
        return $this->doFetch();
    } 
    
    public function doFields()
    {
        $arguments = func_get_args();
        $this->getQueryParameters()->setFields($arguments);
        return $this->wrapper;
    }    
    
    public function doFilter()
    {
        $arguments = func_get_args();
        if (count($arguments) == 2 && is_array($arguments[1])) {
            $filter = $arguments[0];
            $bind = $arguments[1];
        } else {
            $filter = array_shift($arguments);
            $bind = $arguments;
        }
        $filterCompiler = new FilterCompiler();
        $this->getQueryParameters()->setRawFilter(
            $filterCompiler->compile($filter),
            $filterCompiler->rewriteBoundData($bind)
        );
        return $this->wrapper;
    }    
    
    public function doUpdate($data)
    {
        $this->adapter->getDriver()->beginTransaction();
        $parameters = $this->getQueryParameters();
        $this->adapter->bulkUpdate($data, $parameters);
        $this->adapter->getDriver()->commit();
        $this->resetQueryParameters();
    }  
    
    public function doDelete()
    {
        $this->adapter->getDriver()->beginTransaction();
        $parameters = $this->getQueryParameters(false);

        if($parameters === null) {
            $primaryKey = $this->getDescription()['primary_key'];
            $parameters = $this->wrapper->getQueryParameters();
            $data = $this->getData();
            $keys = [];

            foreach($data as $datum) {
                if($this->deleteItem($primaryKey, $datum)) {
                    $keys[] = $datum[$primaryKey];
                }
            }

            $parameters->addFilter($primaryKey[0], $keys);
            $this->adapter->delete($parameters);
        } else {
            $this->adapter->delete($parameters);
        }

        $this->adapter->getDriver()->commit();
        $this->resetQueryParameters();
    }    
    
    public function runDynamicMethod($arguments)
    {
        switch($this->pendingMethod['method']) {
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
        
        foreach($this->dynamicMethods as $regexp) {
            if(preg_match($regexp, $method, $matches)) {
                $return = true;
                $this->pendingMethod = $matches;
                break;
            }
        }
        
        return $return;
    }
}

