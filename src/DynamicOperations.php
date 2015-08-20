<?php

namespace ntentan\nibii;

use ntentan\utils\Text;
use ntentan\utils\Utils;

class DynamicOperations
{
    private $wrapper;
    /**
     *
     * @var \ntentan\nibii\DriverAdapter
     */
    private $adapter;
    private $queryParameters;
    private $data;
    private $invalidFields;

    public function __construct($wrapper, $adapter)
    {
        $this->wrapper = $wrapper;
        $this->adapter = $adapter;
    }

    public function perform($name, $arguments)
    {
        if (array_search($name,
            [ 
                'fetch', 'fetchFirst', 'filter', 'query',
                'fields', 'update', 'delete', 'cover',
                'count', 'limit', 'offset', 'filterBy', 'sortBy', 'save'
            ]
        ) !== false) {
            $method = "do{$name}";
            return call_user_func_array([$this, $method], $arguments);
        } else if (preg_match("/(filterBy)(?<variable>[A-Z][A-Za-z]+){1}/", $name, $matches)) {
            $this->getQueryParameters()->addFilter(Text::deCamelize($matches['variable']), $arguments);
            return $this->wrapper;
        } else if (preg_match("/(sort)(?<direction>Asc|Desc)?(By)(?<variable>[A-Z][A-Za-z]+){1}/", $name, $matches)) {
            $this->getQueryParameters()->addSort(Text::deCamelize($matches['variable']), $matches['direction']);
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

    private function doFetch($id = null)
    {
        $parameters = $this->getFetchQueryParameters($id);
        $data = $this->adapter->select($parameters);
        $this->wrapper->setData($data);
        $this->resetQueryParameters();
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
        $this->resetQueryParameters();
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
        $this->resetQueryParameters();
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
    
    public function validate()
    {
        $valid = true;
        $validator = Utils::factory($this->validator,
            function() {
                return new Validator($this->wrapper->getDescription());
            }
        );
        $data = isset(func_get_args()[0]) ? [func_get_args()[0]] : $this->getData();

        foreach($data as $datum) {
            if(!$validator->validate($datum)) {
                $valid = false;
            }
        }

        if($valid === false) {
            $valid = $validator->getInvalidFields();
        }

        return $valid;
    }

    private function assignValue(&$property, $value)
    {
        if($this->wrapper->hasMultipleData()) {
            $property = $value;
        } else {
            $property = $value[0];
        }
    }

    private function isPrimaryKeySet($primaryKey, $data)
    {
        foreach($primaryKey as $keyField) {
            if(!isset($data[$keyField])) {
                break;
            }
            if($data[$keyField] !== '' && $data[$keyField] !== null) {
                return true;
            }
        }
        return false;
    }

    private function saveRecord($datum, $primaryKey)
    {
        $status = [
            'success' => true,
            'pk_assigned' => null,
            'invalid_fields' => []
        ];
        $pkSet = $this->isPrimaryKeySet($primaryKey, $datum);

        if($pkSet) {
            $this->wrapper->preUpdateCallback();
        } else {
            $this->wrapper->preSaveCallback();
        }
        
        $validity = $this->validate($datum);

        if($validity !== true) {
            $status['invalid_fields'] = $validity;
            $status['success'] = false;
            return $status;
        }

        if($this->isPrimaryKeySet($primaryKey, $datum)) {
            $this->adapter->update($datum);
            $this->wrapper->postUpdateCallback();
        } else {
            $this->adapter->insert($datum);
            $status['pk_assigned'] = $this->adapter->getDriver()->getLastInsertId();
            $this->wrapper->postSaveCallback($status['pk_assigned']);
        }
        $this->wrapper->postSaveCallback($status['pk_assigned']);

        return $status;
    }

    private function doSave()
    {
        $invalidFields = [];
        $data = $this->wrapper->getData();
        $this->adapter->setModel($this->wrapper);
        $primaryKey = $this->wrapper->getDescription()->getPrimaryKey();
        $singlePrimaryKey = null;
        $succesful = true;;

        if (count($primaryKey) == 1) {
            $singlePrimaryKey = $primaryKey[0];
        }
        
        // Assign an empty array to force a validation error for empty models
        if(empty($data)) {
            $data = [[]];
        }

        $this->adapter->getDriver()->beginTransaction();

        foreach($data as $i => $datum) {
            $status = $this->saveRecord($datum, $primaryKey);
            
            if(!$status['success']) {
                $succesful = false;
                $invalidFields[$i] = $status['invalid_fields'];
                $this->adapter->getDriver()->rollback();
                continue;
            }

            if($singlePrimaryKey) {
                $data[$i][$singlePrimaryKey] = $status['pk_assigned'];
            }
        }
        
        if($succesful) {
            $this->assignValue($this->data, $data);
            $this->adapter->getDriver()->commit();
        } else {
            $this->assignValue($this->invalidFields, $invalidFields);
        }

        return $succesful;
    }
    
    public function getData() 
    {
        return $this->data;
    }
    
    public function getInvalidFields()
    {
        return $this->invalidFields;
    }
}
