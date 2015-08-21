<?php

namespace ntentan\nibii;

use ntentan\utils\Text;
use ntentan\utils\Utils;

class Operations
{
    private $wrapper;
    /**
     *
     * @var \ntentan\nibii\DriverAdapter
     */
    private $adapter;
    private $queryOperations;
    private $dataOperations;
    
    const QUERY_OPERATIONS = [
        'fetch', 'fetchFirst', 'filter', 'query', 'fields', 
        'cover', 'limit', 'offset', 'filterBy', 'sortBy',
        'update', 'delete', 'count'
    ];
    
    const DATA_OPERATIONS = [
        'save'
    ];
    
    public function __construct($wrapper, $adapter)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;
        $this->queryOperations = new QueryOperations($wrapper, $adapter);
        $this->dataOperations = new DataOperations($wrapper, $adapter, $this->queryOperations);
    }

    public function perform($name, $arguments)
    {
        if (array_search($name, self::QUERY_OPERATIONS) !== false) {
            return call_user_func_array([$this->queryOperations, "do$name"], $arguments);
        } else if (array_search($name, self::DATA_OPERATIONS) !== false){
            return call_user_func_array([$this->dataOperations, "do$name"], $arguments);
        } else if($this->queryOperations->initDynamicMethod($name)) {
            return $this->queryOperations->runDynamicMethod($arguments);
        } else {
            throw new NibiiException("Method {$name} not found");
        }
    }
    
    private function doLimit($numItems)
    {
        $this->getQueryParameters()->setLimit($numItems);
        return $this->wrapper;
    }
    
    private function doOffset($offset)
    {
        $this->getQueryParameters()->setOffset($offset);
        return $this->wrapper;
    }
    
    private function doCount()
    {
        return $this->adapter->count($this->getQueryParameters());
    }

    private function doCover()
    {
        $parameters = $this->getQueryParameters();
        $parameters->setEagerLoad(func_get_args());
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
    
    public function getData() 
    {
        return $this->dataOperations->getData();
    }
    
    public function getInvalidFields()
    {
        return $this->dataOperations->getInvalidFields();
    }
}
