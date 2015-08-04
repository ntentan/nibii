<?php

namespace ntentan\nibii;

use ntentan\utils\Text;

class DynamicOperations
{
    private $wrapper;
    private $adapter;
    private $queryParameters;
    
    public function __construct($wrapper, $adapter)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;
    }
    
    public function perform($name, $arguments)
    {
        if (array_search($name, ['fetch', 'fetchFirst', 'filter', 'fields', 'update', 'delete']) !== false) {
            $method = "do{$name}";
            return call_user_func_array([$this, $method], $arguments);
        } else if (preg_match("/(filterBy)(?<variable>[A-Za-z]+)/", $name, $matches)) {
            $this->getQueryParameters()->addFilter(Text::deCamelize($matches['variable']), $arguments);
            return $this->wrapper;
        } else if (preg_match("/(fetch)(?<first>First)?(With)(?<variable>[A-Za-z]+)/", $name, $matches)) {
            $parameters = $this->getQueryParameters();
            $parameters->addFilter(Text::deCamelize($matches['variable']), $arguments);
            if ($matches['first'] === 'First') {
                $parameters->setFirstOnly(true);
            }
            return $this->doFetch();    
        } else {
            throw new NibiiException("Method {$name} not found");
        }
    }
    
    private function doFetch($id = null)
    {
        $parameters = $this->getQueryParameters();
        if ($id !== null) {
            $description = $this->wrapper->getDescription();
            $parameters->addFilter($description['primary_key'][0], [$id]);
            $parameters->setFirstOnly(true);
        }
        $this->wrapper->setData($this->adapter->select($parameters));
        return $this->wrapper;
    }
    
    private function deleteItem($primaryKey, $data)
    {   
        if($this->isPrimaryKeySet($primaryKey, $data)) {
            return true;
        } else {
            return false;
        }
    }
    
    private function doDelete()
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
    }

    private function doFetchFirst()
    {
        $this->getQueryParameters()->setFirstOnly(true);
        return $this->doFetch();
    }

    private function doFilter()
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

    private function doFields()
    {
        $arguments = func_get_args();
        $this->getQueryParameters()->setFields($arguments);
        return $this->wrapper;
    }
    
    private function doUpdate($data)
    {
        $this->adapter->getDriver()->beginTransaction();
        $parameters = $this->getQueryParameters();
        $this->adapter->bulkUpdate($data, $parameters);
        $this->adapter->getDriver()->commit();
    }
    
    /**
     * 
     * @return \ntentan\nibii\QueryParameters
     */
    private function getQueryParameters($instantiate = true)
    {
        if ($this->queryParameters === null && $instantiate) {
            $this->queryParameters = new QueryParameters(
                $this->adapter->getDriver(), $this->wrapper->getTable()
            );
        }
        return $this->queryParameters;
    }     
}
